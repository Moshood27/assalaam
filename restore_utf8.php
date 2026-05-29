<?php
function restore_file($path) {
    $content = file_get_contents($path);
    // Peel back layers of encoding
    $current = $content;
    for ($i = 0; $i < 3; $i++) {
        $decoded = @iconv('UTF-8', 'Windows-1252//IGNORE', $current);
        if ($decoded && $decoded !== $current && mb_check_encoding($decoded, 'UTF-8')) {
            $current = $decoded;
        } else {
            break;
        }
    }
    if ($current !== $content) {
        file_put_contents($path, $current);
        echo "Restored: $path\n";
    }
}

restore_file('frontend/src/views/Dashboard.vue');
restore_file('frontend/src/views/Attendance.vue');
// Also process dist assets
$it = new RecursiveDirectoryIterator('frontend/dist/assets');
foreach (new RecursiveIteratorIterator($it) as $file) {
    if ($file->isDir()) continue;
    if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['js', 'css'])) {
        restore_file($file->getPathname());
    }
}
echo "Done!\n";
