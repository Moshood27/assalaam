<?php
require 'backend/vendor/autoload.php';

// Mock some things if needed, or just see if the class can be loaded
try {
    if (class_exists('App\Models\User')) {
        echo "User class loaded successfully\n";
    } else {
        echo "User class NOT found (maybe autoloader issue for App namespace)\n";
    }
} catch (\Throwable $e) {
    echo "Error loading User class: " . $e->getMessage() . "\n";
    echo "At " . $e->getFile() . ":" . $e->getLine() . "\n";
}
