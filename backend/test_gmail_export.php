<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$gmailMembersCount = User::member()
    ->where('email', 'like', '%@gmail.com')
    ->count();

echo "Found $gmailMembersCount members with Gmail accounts.\n";

$emails = User::member()
    ->where('email', 'like', '%@gmail.com')
    ->limit(5)
    ->pluck('email');

foreach ($emails as $email) {
    echo " - $email\n";
}
