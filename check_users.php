<?php
putenv('DB_HOST=127.0.0.1');
putenv('DB_PORT=33060');

require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "Total users: " . User::count() . "\n";
foreach (User::limit(5)->get() as $user) {
    echo "ID: {$user->id}, Name: {$user->name}, Surname: {$user->surname}, Branch: {$user->branch_id}\n";
}
