<?php
/**
 * Binary-safe encoding repair script v6
 * Recursively decodes multi-encoded UTF-8 strings
 */

function recursive_decode($content) {
    // These markers indicate double or triple encoding
    $markers = ["\xc3\x83", "\xc3\xa2", "\xc3\x85", "\xc2\xad", "\xc2\xa0"];
    
    $has_marker = false;
    foreach ($markers as $m) {
        if (strpos($content, $m) !== false) {
            $has_marker = true;
            break;
        }
    }
    
    if (!$has_marker) return $content;

    // Specific mapping for common triple-encoded patterns that iconv might miss or mangle
    $extra_replacements = [
        'c383c2b0c385c2b8c3a2e282ace284a2c385c2bd' => "\xf0\x9f\x92\x8e", // 💎
        'c383c2b0c385c2b8c3a2e282ace28099c382c2b0' => "\xf0\x9f\x92\xb0", // 💰
        'c383c2b0c385c2b8c3a2e282ace28099c382c2b3' => "\xf0\x9f\x92\xb3", // 💳
        'c383c2b0c385c2b8c3a2e282ace28099c382c2ac' => "\xf0\x9f\x92\xac", // 💬
        'c383c2b0c385c2b8c3a2e282ace2809cc382c2a6' => "\xf0\x9f\x93\xa6", // 📦
        'c383c2b0c385c2b8c3a2e282ace2809cc382c28d' => "\xf0\x9f\x93\x8d", // 📍
        'c383c2b0c385c2b8c3a2e282ace2809cc382c2b6' => "\xf0\x9f\x93\xb6", // 📶
        'c383c2b0c385c2b8c3a2e282ace2809cc382c28a' => "\xf0\x9f\x93\x8a", // 📊
        'c383c2b0c385c2b8c3a2e282ace2809cc382c2b8' => "\xf0\x9f\x93\xb8", // 📸
        'c383c2b0c385c2b8c3a2e280a2c385c292' => "\xf0\x9f\x95\x8c", // 🕌
        'c383c2b0c385c2b8c3a2e280a2e284a2' => "\xf0\x9f\x95\x8b", // 🕋
        'c383c2b0c385c2b8c3a2e28094c382c2b3' => "\xf0\x9f\x97\xb3", // 🗓️
        'c383c2b0c385c2b8c3a2e2809cc382c28b' => "\xf0\x9f\x93\x8b", // 📋
        'c383c2b0c385c2b8c3a2e2809bc382c292' => "\xf0\x9f\x9b\x92", // 🛒
        'c383c2a2e282ace284a2' => "'", // ’
        'c383c2a2e282ace2809c' => '"', // “
        'c383c2a2e282ace2809d' => '"', // ”
        'c383c2a2e282ace280a6' => '...', // …
        'c383c2a2e282ace282ac' => '₦', // ₦ (some triple variations)
    ];

    foreach ($extra_replacements as $hex => $val) {
        $search = pack('H*', $hex);
        $content = str_replace($search, $val, $content);
    }

    // Now try generic decode for anything left
    $current = $content;
    for ($i = 0; $i < 2; $i++) {
        $decoded = @iconv('UTF-8', 'Windows-1252//IGNORE', $current);
        if ($decoded && $decoded !== $current && mb_check_encoding($decoded, 'UTF-8')) {
            $current = $decoded;
        } else {
            break;
        }
    }
    
    return $current;
}

function process_dir($dir) {
    if (!is_dir($dir)) return;
    $it = new RecursiveDirectoryIterator($dir);
    foreach (new RecursiveIteratorIterator($it) as $file) {
        if ($file->isDir()) continue;
        $path = $file->getPathname();
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (!in_array($ext, ['php', 'vue', 'js', 'html', 'json', 'md', 'css'])) continue;
        if (strpos($path, 'node_modules') !== false) continue;
        if (strpos($path, 'vendor') !== false) continue;
        if (strpos($path, 'storage') !== false) continue;

        $content = file_get_contents($path);
        $fixed = recursive_decode($content);

        if ($fixed !== $content) {
            file_put_contents($path, $fixed);
            echo "Fixed: $path\n";
        }
    }
}

echo "Starting recursive fix v6...\n";
process_dir(__DIR__ . '/frontend/src');
process_dir(__DIR__ . '/frontend/dist');
process_dir(__DIR__ . '/backend/app');
process_dir(__DIR__ . '/backend/resources/views');
echo "Done!\n";
