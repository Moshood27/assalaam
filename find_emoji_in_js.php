<?php
$file = 'frontend/dist/assets/index-Cmuo8c_W.js';
$content = file_get_contents($file);
$search = "\xf0\x9f\x92\x8e";
$pos = strpos($content, $search);
if ($pos !== false) {
    echo "FOUND Diamond Emoji (f0 9f 92 8e) at $pos\n";
    echo "Context: " . substr($content, $pos - 20, 40) . "\n";
} else {
    echo "NOT FOUND Diamond Emoji in JS\n";
}

$search_bad = "\xc3\xb0\xc2\x9f\xc2\x92\xc2\x8e";
$pos_bad = strpos($content, $search_bad);
if ($pos_bad !== false) {
    echo "FOUND CORRUPTED Diamond Emoji at $pos_bad\n";
}
