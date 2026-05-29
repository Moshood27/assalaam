<?php
$content = file_get_contents('frontend/src/views/Dashboard.vue');
if (strpos($content, "\xe2\x82\xa6") !== false) {
    echo "FOUND Naira in Dashboard.vue\n";
} else {
    echo "NOT FOUND Naira in Dashboard.vue\n";
}
if (strpos($content, "\xf0\x9f\x92\x8e") !== false) {
    echo "FOUND Diamond Emoji in Dashboard.vue\n";
}
