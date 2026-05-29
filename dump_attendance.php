<?php
$content = file_get_contents('frontend/src/views/Attendance.vue');
echo "Attendance.vue hex of start:\n";
echo bin2hex(substr($content, 0, 1000));
