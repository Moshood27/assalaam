<?php

use App\Services\ChatService;
use Illuminate\Support\Facades\Facade;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $chatService = app(ChatService::class);
    echo "Calling getChatAnalytics...\n";
    $analytics = $chatService->getChatAnalytics();
    echo "Success! Analytics calculated.\n";
    print_r($analytics);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
