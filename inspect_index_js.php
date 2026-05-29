<?php
$file = 'frontend/dist/assets/index-Cmuo8c_W.js';
$content = file_get_contents($file);
$pos = strpos($content, 'Qard Hasan Status');
if ($pos !== false) {
    echo "Found 'Qard Hasan Status' at $pos\n";
    echo "Context: " . substr($content, $pos, 100) . "\n";
    echo "Hex: " . bin2hex(substr($content, $pos, 100)) . "\n";
} else {
    echo "Not found\n";
}
