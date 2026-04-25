<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumnListing('users');
echo "Columns in users table:\n";
print_r($columns);

if (Schema::hasColumn('users', 'pregnancy_request_status')) {
    echo "\npregnancy_request_status EXISTS\n";
} else {
    echo "\npregnancy_request_status MISSING\n";
}
