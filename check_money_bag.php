<?php
$file = 'frontend/dist/assets/index-Cmuo8c_W.js';
$content = file_get_contents($file);
$search = "\xf0\x9f\x92\xb0";
if (strpos($content, $search) !== false) {
    echo "FOUND Money Bag Emoji (f0 9f 92 b0)\n";
} else {
    echo "NOT FOUND Money Bag Emoji\n";
}
