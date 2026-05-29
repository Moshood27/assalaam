<?php
$content = file_get_contents('frontend/src/views/VendorSettlements.vue');
echo bin2hex(substr($content, strpos($content, 'availableBalance'), 100));
