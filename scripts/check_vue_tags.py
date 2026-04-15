import os
import re

def check_balanced_tags(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Extract template part
    match = re.search(r'<template>(.*)</template>', content, re.DOTALL)
    if not match:
        return True, 0
    
    template = match.group(1)
    
    # Better tag counting
    # Remove comments
    template = re.sub(r'<!--.*?-->', '', template, flags=re.DOTALL)
    
    open_divs = len(re.findall(r'<div\b', template))
    close_divs = len(re.findall(r'</div\b', template))
    self_closing = len(re.findall(r'<div[^>]*/>', template))
    
    actual_open = open_divs - self_closing
    
    if actual_open != close_divs:
        return False, actual_open - close_divs
    return True, 0

def main():
    views_dir = 'frontend/src/views'
    unbalanced = []
    for filename in os.listdir(views_dir):
        if filename.endswith('.vue'):
            path = os.path.join(views_dir, filename)
            try:
                is_balanced, diff = check_balanced_tags(path)
                if not is_balanced:
                    unbalanced.append((filename, diff))
            except Exception as e:
                print(f"Error checking {filename}: {e}")

    if unbalanced:
        print("Found unbalanced div tags in the following files:")
        for f, d in unbalanced:
            print(f'  {f}: diff={d}')
    else:
        print("All div tags are balanced.")

if __name__ == "__main__":
    main()
