<?php
require 'backend/vendor/autoload.php';
$app = require_once 'backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumnListing('users');
echo "Columns in users table:\n";
print_r($columns);

$migrationFields = ['migrated_at', 'verified_at', 'discrepancy_reported_at'];
foreach ($migrationFields as $field) {
    echo "Checking $field: " . (Schema::hasColumn('users', $field) ? "EXISTS" : "MISSING") . "\n";
}
