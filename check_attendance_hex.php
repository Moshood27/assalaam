<?php
$content = file_get_contents('frontend/src/views/Attendance.vue');
$pos = strpos($content, 'text-5xl mb-4');
if ($pos !== false) {
    echo "Found at $pos\n";
    echo bin2hex(substr($content, $pos, 100)) . "\n";
} else {
    echo "Not found\n";
}
