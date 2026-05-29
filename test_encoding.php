<?php
$double = "Ã°Å¸â€™Å½";
$bytes = mb_convert_encoding($double, 'ISO-8859-1', 'UTF-8');
echo bin2hex($bytes) . "\n";
// f0 9f 92 8e is expected
