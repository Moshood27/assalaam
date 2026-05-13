import re
import sys

def check_tags(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Extract template section
    match = re.search(r'<template>(.*)</template>', content, re.DOTALL)
    if not match:
        print("No template found")
        return
    template = match.group(1)
    
    # Improved regex to find tags across multiple lines
    tag_pattern = re.compile(r'<(/?)([a-zA-Z0-9-]+)(.*?)?(/?)(>)', re.DOTALL)
    
    # We need to know the line number for each match.
    # We'll use finditer on the whole template and calculate line numbers.
    def get_line_number(pos):
        return template.count('\n', 0, pos) + 1

    stack = []
    
    # Remove comments
    clean_template = re.sub(r'<!--.*?-->', lambda m: ' ' * len(m.group(0)), template, flags=re.DOTALL)
    
    for m in tag_pattern.finditer(clean_template):
        is_close = m.group(1) == '/'
        tag_name = m.group(2)
        content = m.group(3) or ''
        is_self_closing = m.group(4) == '/' or content.strip().endswith('/')
        
        # Void elements or common self-closing SVG tags
        if tag_name in ['input', 'img', 'br', 'hr', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse', 'stop', 'use', 'meta', 'link', 'area', 'base', 'col', 'embed', 'keygen', 'param', 'source', 'track', 'wbr']:
            continue
            
        line_no = get_line_number(m.start())
        if is_close:
            if not stack:
                print(f"Unexpected closing tag </{tag_name}> at line {line_no}")
            else:
                last_tag, last_line = stack.pop()
                if last_tag != tag_name:
                    print(f"Mismatched tag at line {line_no}: expected </{last_tag}> (from line {last_line}), found </{tag_name}>")
                    # stack.append((last_tag, last_line)) 
        elif not is_self_closing:
            stack.append((tag_name, line_no))
        
        # print(f"DEBUG: Tag {tag_name} (close={is_close}, self={is_self_closing}) at line {line_no}. Stack size: {len(stack)}")
        if is_close and tag_name == 'template':
            break
    
    if stack:
        print(f"Stack size: {len(stack)}")
        for tag, line in stack:
            print(f"Unclosed tag <{tag}> at line {line}")

if __name__ == "__main__":
    check_tags(sys.argv[1])
