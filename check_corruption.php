<?php
$patterns = [
    'c383c2a2' => 'Generic Triple (Ã¢)',
    'c3a2e282ac' => 'Generic Double (â‚¬)',
    'c383c2a2e282ace282ac' => 'Naira Triple',
    'c3a2e282acc2a6' => 'Naira Double',
    'c383c2a2c382c29c' => '✓œ Triple',
    'c3a2e284a2' => 'â„¢',
    'c383c2a2c385' => 'Emoji Triple Start',
    'c383c2a2c382c29d' => 'Emoji Triple 2',
    'c383c2a2c382c2ba' => 'Emoji Triple 3',
];

function scan($path, $patterns) {
    if (is_dir($path)) {
        $it = new RecursiveDirectoryIterator($path);
        foreach (new RecursiveIteratorIterator($it) as $file) {
            if ($file->isDir()) continue;
            check_file($file->getPathname(), $patterns);
        }
    } else {
        check_file($path, $patterns);
    }
}

function check_file($path, $patterns) {
    $content = file_get_contents($path);
    $hex = bin2hex($content);
    foreach ($patterns as $p => $name) {
        if (strpos($hex, $p) !== false) {
            echo "MATCH: $name in $path\n";
            $pos = strpos($hex, $p) / 2;
            $start = max(0, $pos - 20);
            $context = substr($content, $start, 50);
            echo "Context: " . $context . " (" . bin2hex(substr($content, $pos, 10)) . ")\n\n";
        }
    }
}

echo "--- Checking Profile.vue ---\n";
scan('frontend/src/views/Profile.vue', $patterns);

echo "--- Checking Backend ---\n";
scan('backend', $patterns);

echo "--- Checking Root MD files ---\n";
$mds = glob('*.md');
foreach ($mds as $md) {
    check_file($md, $patterns);
}

echo "--- Checking Root PS1/BAT/PHP files ---\n";
$scripts = array_merge(glob('*.ps1'), glob('*.bat'), glob('*.php'));
foreach ($scripts as $s) {
    if ($s === 'check_corruption.php') continue;
    check_file($s, $patterns);
}
