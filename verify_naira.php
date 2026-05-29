<?php
$files = [
    'frontend/src/components/LoanScheduleModal.vue',
    'frontend/src/views/Attendance.vue',
    'frontend/src/views/Dashboard.vue'
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    echo "$file: ";
    if (strpos($content, "\xe2\x82\xa6") !== false) echo "FOUND Naira. ";
    if (strpos($content, "\xc3\xa2") !== false) echo "FOUND Corrupted Ã¢. ";
    if (strpos($content, "Ã°Å¸") !== false) echo "FOUND Corrupted Emoji. ";
    echo "\n";
}
