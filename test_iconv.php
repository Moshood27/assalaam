<?php
$double = "Ã°Å¸â€™Å½";
$bytes = iconv('UTF-8', 'Windows-1252//IGNORE', $double);
echo bin2hex($bytes) . "\n";
