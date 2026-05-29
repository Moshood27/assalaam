<?php
$content = file_get_contents('frontend/src/views/Dashboard.vue');
// Find any sequence of 2+ characters that are in the C3, C2, E2 range
preg_match_all('/[\xc2-\xc3]./u', $content, $matches);
foreach ($matches[0] as $m) {
    echo bin2hex($m) . " ";
}
echo "\n";
