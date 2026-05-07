<?php
require 'backend/vendor/autoload.php';
$app = require_once 'backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ChatCannedResponse;

$options = ChatCannedResponse::distinct()->pluck('category', 'category')->toArray();
var_dump($options);
