<?php
require 'backend/vendor/autoload.php';

try {
    $user = new App\Models\User();
    echo "Success: User model loaded.\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack Trace: " . $e->getTraceAsString() . "\n";
}
