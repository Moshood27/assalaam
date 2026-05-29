<?php
$content = file_get_contents('frontend/src/views/Dashboard.vue');
$pos = strpos($content, 'bg-blue-50 rounded-2xl flex items-center justify-center text-xl');
if ($pos !== false) {
    echo bin2hex(substr($content, $pos + 65, 10));
} else {
    echo "Not found";
}
