<?php
/**
 * Binary-safe encoding repair script v5
 * Uses HEX patterns to be absolutely sure of matches
 */

function hex_to_string($hex) {
    return pack('H*', str_replace(' ', '', $hex));
}

function fix_content($content) {
    $replacements = [
        // Naira symbol (triple encoded)
        'c3a2e282acc2a6' => "\xe2\x82\xa6", // ₦
        'c3a2c282c2a6' => "\xe2\x82\xa6", // ₦ (variation)
        'c3a2e2809ac2a6' => "\xe2\x82\xa6", // ₦ (variation)
        'e2809ac2a6' => "\xe2\x82\xa6", // ₦ (double encoded partial)
        
        // Emojis (double encoded)
        'c3b0c5b8e28099c5bd' => "\xf0\x9f\x92\x8e", // 💎
        'c3b0c5b8e28099c2b0' => "\xf0\x9f\x92\xb0", // 💰
        'c3b0c5b8e28099c2b3' => "\xf0\x9f\x92\xb3", // 💳
        'c3b0c5b8e28099c2ac' => "\xf0\x9f\x92\xac", // 💬
        'c3b0c5b8e2809cc2a6' => "\xf0\x9f\x93\xa6", // 📦
        'c3b0c5b8c592e284a2' => "\xf0\x9f\x8c\x99", // 🌙
        'c3b0c5b8e2809cc28d' => "\xf0\x9f\x93\x8d", // 📍
        'c3b0c5b8c2a4c2bd' => "\xf0\x9f\xa4\x9d", // 🤝
        'c3b0c5b8e2809cc2b6' => "\xf0\x9f\x93\xb6", // 📶
        'c3b0c5b8e2809cc28a' => "\xf0\x9f\x93\x8a", // 📊
        'c3b0c5b8e2809cc38b' => "\xf0\x9f\x93\x88", // 📈
        'c3b0c5b8e2809bc2a1efb88f' => "\xf0\x9f\x9b\xa1\xef\xb8\x8f", // 🛡️
        'c3b0c5b8c2a7c2be' => "\xf0\x9f\xa7\xbe", // 🧾
        'c3b0c5b8e2809bc292' => "\xf0\x9f\x9b\x92", // 🛒
        'c3b0c5b8c2aac299' => "\xf0\x9f\xaa\x99", // 🪙
        'c3b0c5b8e2809cc2b8' => "\xf0\x9f\x93\xb8", // 📸
        'c3b0c5b8e2809dc2b2' => "\xf0\x9f\x94\xb2", // 🔳
        'c3b0c5b8e28094c2b3efb88f' => "\xf0\x9f\x97\xb3\xef\xb8\x8f", // 🗓️
        'c3b0c5b8e28094c2b3' => "\xf0\x9f\x97\xb3", // 🗓️
        'c3b0c5b8e280a2c592' => "\xf0\x9f\x95\x8c", // 🕌
        'c3b0c5b8c2a5c2a3' => "\xf0\x9f\xa5\xa3", // 🥣
        'c3b0c5b8e280a2e2809b' => "\xf0\x9f\x95\x8b", // 🕋
        'c3b0c5b8e28098c2b6' => "\xf0\x9f\x91\xb6", // 👶
        'c3b0c5b8e2809cc28b' => "\xf0\x9f\x93\x8b", // 📋
        'c3b0c5b8e2808fc2aa' => "\xf0\x9f\x8f\xaa", // 🏪
        'c3b0c5b8e28098c2ae' => "\xf0\x9f\x91\xae", // 👮
        'c3b0c5b8e2809dc294' => "\xf0\x9f\x94\x94", // 🔔
        'c3b0c5b8e28094e2809cef8f' => "\xf0\x9f\x97\x93\xef\xb8\x8f", // 🗓️
        'c3b0c5b8e280a2e28092' => "\xf0\x9f\x95\x92", // 🕙
        'c3b0c5b8e284a2c28f' => "\xf0\x9f\x99\x8f", // 🙏
        'c3a2c28f33' => "\xe2\x8c\xb3", // ⏳
        'c3b0c5b8e2809dc291' => "\xf0\x9f\x94\x91", // 🔑
        'c3b0c5b8e2809cc29d' => "\xf0\x9f\x93\x9d", // 📝
        'c3b0c5b8c28dc2bc' => "\xf0\x9f\x8d\xbc", // 🍼
        'c3b0c5b8e2809cc2a5' => "\xf0\x9f\x93\xa5", // 📥
        'c3b0c5b8e28099c2b5' => "\xf0\x9f\x92\xb5", // 💵
        'c3b0c5b8e284a2c38b' => "\xf0\x9f\x99\x88", // 🙊
        'c3b0c5b8e28098c281efb88f' => "\xf0\x9f\x91\x81\xef\xb8\x8f", // 👁️
        'c3b0c5b8c592c2a2' => "\xf0\x9f\x8f\xa2", // 🏢
        'c3b0c5b8c592c2a6' => "\xf0\x9f\x8f\xa6", // 🏦
        
        // Symbols & Typography
        'c3a2c593e280a2' => "\xe2\x9c\x95", // ✕
        'c3a2c593e280a6' => "\xe2\x9c\x94", // ✔
        'c3a2c593e28094' => "\xe2\x9c\x93", // ✓
        'c3a2e282ace2809c' => "\xe2\x9c\x93", // ✓ variation
        'c3a2e282ac22' => "\xe2\x80\x9d", // ”
        'c3a2e2809ce280a2' => "\xe2\x80\xa2", // •
        'c3a2e2809ce280a6' => "\xe2\x80\xa6", // …
        'c3a2e2809ce284a2' => "\xe2\x80\x99", // ’
        'c3a2e2809ce2809c' => "\xe2\x80\x9c", // “
        'c3a2e2809ce2809d' => "\xe2\x80\x9d", // ”
        'c3a2e2809ce28094' => "\xe2\x80\x94", // —
        'c3a2c592e28093' => "\xe2\x9a\x96", // ⚖️
        'c3a2c592e28093efb88f' => "\xe2\x9a\x96\xef\xb8\x8f", // ⚖️
        'c3a2c2ad2a' => "\xe2\xad\x90", // ⭐
        'c3a2cb86e28099' => "\xe2\x88\x92", // −
        'c2a0' => ' ', // Non-breaking space
    ];

    $search = [];
    $replace = [];
    foreach ($replacements as $hex => $val) {
        $search[] = hex_to_string($hex);
        $replace[] = $val;
    }

    return str_replace($search, $replace, $content);
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

echo "Starting comprehensive hex fix v5...\n";
process_dir(__DIR__ . '/frontend/src');
process_dir(__DIR__ . '/frontend/dist');
process_dir(__DIR__ . '/backend/app');
process_dir(__DIR__ . '/backend/resources/views');
echo "Done!\n";
