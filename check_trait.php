<?php
require 'backend/vendor/autoload.php';

try {
    if (trait_exists('Spatie\Activitylog\Models\Concerns\LogsActivity')) {
        echo "Trait Spatie\Activitylog\Models\Concerns\LogsActivity exists\n";
    } else {
        echo "Trait Spatie\Activitylog\Models\Concerns\LogsActivity does NOT exist\n";
    }
    
    if (trait_exists('Spatie\Activitylog\Traits\LogsActivity')) {
        echo "Trait Spatie\Activitylog\Traits\LogsActivity exists\n";
    } else {
        echo "Trait Spatie\Activitylog\Traits\LogsActivity does NOT exist\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
