<?php
$content = file_get_contents('frontend/src/views/Attendance.vue');
if (strpos($content, "Ã°Å¸â€”â€œÃ¯Â¸Â") !== false) {
    echo "STILL EXISTS in Attendance.vue\n";
} else {
    echo "NOT FOUND in Attendance.vue\n";
}
