<?php
$content = file_get_contents('frontend/src/views/Dashboard.vue');
$pos = strpos($content, 'text-rose-600">');
if ($pos !== false) {
    echo bin2hex(substr($content, $pos + 15, 20));
} else {
    echo "Not found";
}
