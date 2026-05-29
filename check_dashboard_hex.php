<?php
$content = file_get_contents('frontend/src/views/Dashboard.vue');
$pos = strpos($content, 'kpis.total_due_amount');
if ($pos !== false) {
    echo bin2hex(substr($content, $pos - 20, 40));
} else {
    echo "Not found";
}
