<?php
$content = file_get_contents('frontend/src/views/Dashboard.vue');
preg_match_all('/Ã°Å¸.{1,10}/u', $content, $matches);
print_r($matches);
