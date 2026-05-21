<?php
require 'vendor/autoload.php';

try {
    class TestClass {
        use \Spatie\Activitylog\Models\Concerns\LogsActivity;

        public function getActivitylogOptions(): \Spatie\Activitylog\Support\LogOptions
        {
            return \Spatie\Activitylog\Support\LogOptions::defaults();
        }
    }
    echo "Successfully used Spatie\Activitylog\Models\Concerns\LogsActivity\n";
} catch (\Throwable $e) {
    echo "Failed to use Spatie\Activitylog\Models\Concerns\LogsActivity: " . $e->getMessage() . "\n";
}

try {
    class TestClass2 {
        use \Spatie\Activitylog\Traits\LogsActivity;
    }
    echo "Successfully used Spatie\Activitylog\Traits\LogsActivity\n";
} catch (\Throwable $e) {
    echo "Failed to use Spatie\Activitylog\Traits\LogsActivity: " . $e->getMessage() . "\n";
}
