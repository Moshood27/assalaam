<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$affected = DB::table('user_virtual_accounts')->update([
    'paystack_customer_code' => null,
    'paystack_authorization_code' => null,
    'dva_account_number' => null,
    'dva_bank_name' => null,
    'dva_account_name' => null,
    'dva_verification_meta' => null,
]);

echo "Cleared $affected virtual account records.\n";
