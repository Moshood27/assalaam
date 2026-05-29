<?php
$file = 'frontend/dist/assets/index-Cmuo8c_W.js';
$content = file_get_contents($file);
if (strpos($content, "\xe2\x82\xa6") !== false) {
    echo "FOUND Naira (e2 82 a6) in index.js\n";
} else {
    echo "NOT FOUND Naira (e2 82 a6) in index.js\n";
}

// Check for corrupted Naira Ã¢â€šÂ¦
if (strpos($content, "\xc3\xa2\xe2\x82\xac\xc2\xa6") !== false) {
    echo "FOUND Corrupted Naira (triple/double) in index.js\n";
}

// Check for corrupted Emoji Ã°Å¸â€™Å½ (c3 b0 c2 9f c2 92 c2 8e)
if (strpos($content, "\xc3\xb0\xc2\x9f\xc2\x92\xc2\x8e") !== false) {
    echo "FOUND Corrupted Diamond Emoji in index.js\n";
}
