<?php
function check_file($file) {
    $content = file_get_contents($file);
    $found = [];
    if (strpos($content, "\xc3\xb0\xc2\x9f") !== false) {
        $found[] = "Corrupted Emoji Start (c3 b0 c2 9f)";
    }
    if (strpos($content, "\xc3\xa2\xe2\x82\xac\xc2\xa6") !== false) {
        $found[] = "Corrupted Naira";
    }
    if (!empty($found)) {
        echo "File: $file\n";
        foreach ($found as $f) echo "  - $f\n";
    }
}

$it = new RecursiveDirectoryIterator('frontend/dist/assets');
foreach (new RecursiveIteratorIterator($it) as $file) {
    if ($file->isDir()) continue;
    if (pathinfo($file->getPathname(), PATHINFO_EXTENSION) === 'js') {
        check_file($file->getPathname());
    }
}
