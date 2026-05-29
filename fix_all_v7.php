<?php
/**
 * Binary-safe encoding repair script v7
 * Direct HEX markers replacement for triple-encoded content
 */

function fix_content($content) {
    $replacements = [
        // Triple-encoded markers
        'c383c2b0c385c2b8' => "\xf0\x9f", // ðŸ -> emoji start
        'c383c2a2e282ace282ac' => "\xe2\x82\xa6", // Naira triple
        'c383c2a2e282ace284a2' => "\xe2\x80\x99", // ’ triple
        'c383c2a2e282ace2809c' => "\xe2\x80\x9c", // “ triple
        'c383c2a2e282ace2809d' => "\xe2\x80\x9d", // ” triple
        'c383c2a2e282ace280a6' => "\xe2\x80\xa6", // … triple
        
        // Double-encoded markers
        'c3b0c5b8' => "\xf0\x9f", // ðŸ -> emoji start
        'c3a2e282acc2a6' => "\xe2\x82\xa6", // Naira double
        'e282ac22' => "\xe2\x80\x9d", // ” double variation
        
        // Specific unhandled sequences from find_remaining.php
        'c383c2b0c3a2e282ace284a2c382c292' => "\xf0\x9f\x9b\x92", // 🛒
        'c383c2b0c3a2e282ace2809cc382c28b' => "\xf0\x9f\x93\x8b", // 📋
        'c383c2b0c3a2e280a2c385c292' => "\xf0\x9f\x95\x8c", // 🕌
        'c383c2b0c3a2e280a2e284a2' => "\xf0\x9f\x95\x8b", // 🕋
        'c383c2b0c385c2b8c3a2e28094c382c2b3' => "\xf0\x9f\x97\xb3", // 🗓️
    ];

    foreach ($replacements as $hex => $val) {
        $search = pack('H*', $hex);
        $content = str_replace($search, $val, $content);
    }
    
    // Final pass for common double-encoded chars
    $content = str_replace("\xc3\xa2\xe2\x82\xac\xe2\x84\xa2", "\xe2\x80\x99", $content);
    $content = str_replace("\xc3\xa2\xe2\x82\xac\xc2\xa6", "₦", $content); // Sometimes this happens
    
    return $content;
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
        $fixed = fix_content($content);

        if ($fixed !== $content) {
            file_put_contents($path, $fixed);
            echo "Fixed: $path\n";
        }
    }
}

echo "Starting hex fix v7...\n";
process_dir(__DIR__ . '/frontend/src');
process_dir(__DIR__ . '/frontend/dist');
process_dir(__DIR__ . '/backend/app');
process_dir(__DIR__ . '/backend/resources/views');
echo "Done!\n";
