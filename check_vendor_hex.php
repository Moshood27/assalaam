<?php
$content = file_get_contents('frontend/src/views/VendorSettlements.vue');
$pos = strpos($content, '{{ formatMoney(availableBalance');
if ($pos !== false) {
    echo bin2hex(substr($content, $pos - 10, 20));
} else {
    echo "Not found";
}
