<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Events\SupportMessageSent;
use App\Models\SupportMessage;

$message = SupportMessage::latest()->first();
if (!$message) {
    echo "No message found to test with.\n";
    exit(1);
}

echo "Testing broadcasting SupportMessageSent for user {$message->user_id}...\n";
try {
    event(new SupportMessageSent($message));
    echo "Broadcasted successfully (via ShouldBroadcastNow).\n";
} catch (\Exception $e) {
    echo "Broadcast failed: " . $e->getMessage() . "\n";
}
