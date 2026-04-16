<?php
use App\Models\User;
use App\Models\Scheme;
use App\Models\Contribution;
use App\Http\Controllers\Api\ExportController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$userId = 625;
$user = User::find($userId);

if (!$user) {
    echo "User not found\n";
    exit;
}

$oldMembershipNumber = $user->membership_number;
$user->membership_number = 'ADM/2026/001';
echo "Temporarily setting membership number to " . $user->membership_number . "\n";

$request = new Request(['year' => 2026]);
$request->setUserResolver(fn() => $user);

$controller = $app->make(ExportController::class);

try {
    echo "Attempting to generate PDF for user $userId...\n";
    $response = $controller->downloadPassbook($request);
    echo "Response status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() !== 200) {
        echo "Response content: " . $response->getContent() . "\n";
    } else {
        echo "PDF generated successfully!\n";
    }
} catch (\Throwable $e) {
    echo "Caught exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
