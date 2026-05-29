<?php
$files = glob('frontend/dist/assets/*.js');
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, "\xc3\xb0\xc5\xb8") !== false || strpos($content, "\xc3\xa2\xe2\x82\xac\xc2\xa6") !== false) {
        echo "BAD: $file\n";
    }
}
echo "Check finished.\n";
