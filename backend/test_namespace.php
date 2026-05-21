<?php
require 'vendor/autoload.php';

echo "Testing Spatie\Activitylog\Models\Concerns\LogsActivity...\n";
if (trait_exists('Spatie\Activitylog\Models\Concerns\LogsActivity')) {
    echo "EXISTS: Spatie\Activitylog\Models\Concerns\LogsActivity\n";
} else {
    echo "NOT FOUND: Spatie\Activitylog\Models\Concerns\LogsActivity\n";
}

echo "\nTesting Spatie\Activitylog\Traits\LogsActivity...\n";
if (trait_exists('Spatie\Activitylog\Traits\LogsActivity')) {
    echo "EXISTS: Spatie\Activitylog\Traits\LogsActivity\n";
} else {
    echo "NOT FOUND: Spatie\Activitylog\Traits\LogsActivity\n";
}
