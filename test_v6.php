<?php
function recursive_decode($content) {
    $current = $content;
    for ($i = 0; $i < 3; $i++) {
        $decoded = @iconv('UTF-8', 'Windows-1252//IGNORE', $current);
        echo "Iter $i: " . bin2hex($decoded) . "\n";
        if ($decoded && $decoded !== $current) {
            $current = $decoded;
        } else {
            break;
        }
    }
    return $current;
}

$double = "Ã°Å¸â€™Å½";
echo "Start: " . bin2hex($double) . "\n";
$fixed = recursive_decode($double);
echo "Fixed: " . bin2hex($fixed) . "\n";
