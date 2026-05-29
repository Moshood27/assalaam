<?php
$content = file_get_contents('frontend/src/views/Dashboard.vue');
$search = 'Ã¢â€šÂ¦';
if (strpos($content, $search) !== false) {
    echo "STILL EXISTS at " . strpos($content, $search);
} else {
    echo "NOT FOUND";
}
