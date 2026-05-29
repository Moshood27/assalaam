<?php
/**
 * Binary-safe encoding repair script v4
 * Comprehensive fix for multi-encoded characters
 */

function fix_content($content) {
    $replacements = [
        // Naira symbol (multiple levels of corruption)
        'Ã¢â€šÂ¦' => '₦',
        'â‚¦' => '₦',
        'Ã¢â‚¬Â¦' => '...', // Often confused with Naira triple-encoded
        
        // Emojis (double encoded)
        'Ã°Å¸â€™Å½' => '💎',
        'Ã°Å¸â€™Â°' => '💰',
        'Ã°Å¸â€™Â³' => '💳',
        'Ã°Å¸â€™Â¬' => '💬',
        'Ã°Å¸â€œÂ¦' => '📦',
        'Ã°Å¸Å’â„¢' => '🌙',
        'Ã°Å¸â€œÂ' => '📍',
        'Ã°Å¸Â¤Â' => '🤝',
        'Ã°Å¸â€œÂ¶' => '📶',
        'Ã°Å¸â€œÅ ' => '📊',
        'Ã°Å¸â€œË†' => '📈',
        'Ã°Å¸â€ºÂ¡Ã¯Â¸Â' => '🛡️',
        'Ã°Å¸Â§Â¾' => '🧾',
        'Ã°Å¸â€ºâ€™' => '🛒',
        'Ã°Å¸Âªâ„¢' => '🪙',
        'Ã°Å¸â€œÂ¸' => '📸',
        'Ã°Å¸â€Â²' => '🔳',
        'Ã°Å¸â€”Â³Ã¯Â¸Â' => '🗓️',
        'Ã°Å¸â€”Â³' => '🗓️',
        'Ã°Å¸â€¢Å’' => '🕌',
        'Ã°Å¸Â¥Â£' => '🥣',
        'Ã°Å¸â€¢â€¹' => '🕋',
        'Ã°Å¸â€˜Â¶' => '👶',
        'Ã°Å¸â€œâ€¹' => '📋',
        'Ã°Å¸ÂÂª' => '🏪',
        'Ã°Å¸â€˜Â®' => '👮',
        'Ã°Å¸â€â€' => '🔔',
        'Ã°Å¸â€”â€œÃ¯Â¸Â' => '🗓️',
        'Ã°Å¸â€¢â€™' => '🕙',
        'Ã°Å¸â„¢Â' => '🙏',
        'Ã¢ÂÂ³' => '⏳',
        'Ã°Å¸â€â€˜' => '🔑',
        'Ã°Å¸â€œÂ' => '📝',
        'Ã°Å¸ÂÂ¼' => '🍼',
        'Ã°Å¸ÂÂ¦' => '🏦',
        'Ã°Å¸â€œÂ¥' => '📥',
        'Ã°Å¸Ââ€”Ã¯Â¸Â' => '🏗️',
        'Ã°Å¸â€™Âµ' => '💵',
        'Ã°Å¸â„¢Ë†' => '🙊',
        'Ã°Å¸â€˜ÂÃ¯Â¸Â' => '👁️',
        'Ã°Å¸Å¸Â¢' => '🟢',
        'Ã°Å¸Å½Â' => '🎁',
        'Ã°Å¸â€“Â¼Ã¯Â¸Â' => '🖼️',
        'Ã°Å¸â€œâ€¦' => '📅',
        'Ã¢â€°Ë†' => '≈',
        'Ã¢Å¡Â Ã¯Â¸Â' => '⚠️',
        'Ã¢Å¡â€“Ã¯Â¸Â' => '⚖️',
        'Ã¢Å¡–Ã¯Â¸Â' => '⚖️',
        'Ã¢Å¡' => '⚖️',
        
        // Symbols & Icons
        'Ã¢Å“â€¢' => '✕',
        'Ã¢Å“â€¦' => '✅',
        'Ã¢Å“â€✓' => '✓',
        'Ã¢Å“â€' => '✓',
        'Ã¢Å’â€º' => '⌛',
        'Ã¢ÂÅ’' => '❌',
        'Ã¢Â­Â' => '⭐',
        'Ã¢Ë†â€™' => '−',
        
        // Typography
        'Ã¢â‚¬Â¢' => '•',
        'Ã¢â‚¬Â¦' => '...',
        'â€“' => '–',
        'Ã¢â‚¬â„¢' => "'",
        'Ã¢â‚¬Å“' => '"',
        'Ã¢â‚¬Â' => '"',
        'Ã¢â‚¬â€' => '—',
        'Ã¢â‚¬â€˜' => '‐',
        'Ã‚Â' => '', 
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $content);
}

function process_dir($dir) {
    if (!is_dir($dir)) return;
    $it = new RecursiveDirectoryIterator($dir);
    foreach (new RecursiveIteratorIterator($it) as $file) {
        if ($file->isDir()) continue;
        $path = $file->getPathname();
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (!in_array($ext, ['php', 'vue', 'js', 'html', 'json', 'md'])) continue;
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

echo "Starting comprehensive fix v4...\n";
process_dir(__DIR__ . '/frontend/src');
process_dir(__DIR__ . '/frontend/dist');
process_dir(__DIR__ . '/backend/app');
process_dir(__DIR__ . '/backend/resources/views');
echo "Done!\n";
