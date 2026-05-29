<?php
$lines = file('frontend/src/views/Dashboard.vue');
$line = $lines[160]; // 0-indexed
echo "Line 161: " . $line . "\n";
echo "Hex: " . bin2hex($line) . "\n";
