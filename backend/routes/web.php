<?php

use App\Http\Controllers\Admin\TemplateDownloadController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use App\Http\Controllers\Api\AdminTakafulController;
use App\Http\Controllers\Admin\PrintController;

Route::get('/', function () {
    return view('welcome');
});

// Fallback handler for public storage files when the symlink (public/storage) is missing or inaccessible.
// In normal setups, `php artisan storage:link` creates a symlink and Nginx/Apache serves files directly.
// This route safely serves files from storage/app/public (disk: public) and only runs if the web server
// doesn't serve the static file first.
Route::get('/storage/{path}', function (string $path) {
    // Basic traversal protection
    $path = ltrim($path, '/');
    if (str_contains($path, '..')) {
        abort(404);
    }

    // 1) Prefer files stored on the public disk (storage/app/public)
    if (Storage::disk('public')->exists($path)) {
        $mime = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';
        $stream = Storage::disk('public')->readStream($path);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    // 2) Fallback: support legacy/public uploads under public/<path>
    $publicFile = public_path($path);
    if (is_file($publicFile)) {
        $mime = function_exists('mime_content_type') ? (mime_content_type($publicFile) ?: 'application/octet-stream') : 'application/octet-stream';
        return response()->file($publicFile, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    abort(404);
})->where('path', '.*');

// Explicit proxy endpoint that always goes through Laravel (bypasses web server static file handling).
// Use this to avoid 403s from misconfigured static serving during development.
Route::get('/storage-proxy/{path}', function (string $path) {
    $path = ltrim($path, '/');
    if (str_contains($path, '..')) {
        abort(404);
    }

    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }

    $mime = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';
    $stream = Storage::disk('public')->readStream($path);

    return response()->stream(function () use ($stream) {
        fpassthru($stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
    }, 200, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.*');


// Provide a fallback named 'login' route so middleware can redirect unauthenticated browser requests
// without throwing RouteNotFoundException. For API calls, respond with 401 JSON.
Route::get('/login', function (\Illuminate\Http\Request $request) {
    if ($request->expectsJson() || $request->is('api/*')) {
        return response()->json(['message' => 'Unauthenticated. Please login.'], 401);
    }
    return response('Unauthenticated. Please login via the app.', 401);
})->name('login');


// Web (session-authenticated) export routes for Takaful to support Filament downloads without Bearer tokens.
Route::middleware(['auth'])->prefix('admin/takaful/export')->group(function () {
    Route::get('/ledger.csv', [AdminTakafulController::class, 'exportLedgerCsv'])->name('takaful.web.export.ledger.csv');
    Route::get('/ledger.pdf', [AdminTakafulController::class, 'exportLedgerPdf'])->name('takaful.web.export.ledger.pdf');
    Route::get('/summary.csv', [AdminTakafulController::class, 'exportSummaryCsv'])->name('takaful.web.export.summary.csv');
    Route::get('/summary.pdf', [AdminTakafulController::class, 'exportSummaryPdf'])->name('takaful.web.export.summary.pdf');

    // Printing (Passbooks & Receipts)
    Route::get('/print/passbook/{user}', [PrintController::class, 'passbook'])->name('admin.print.passbook');
    Route::get('/print/wallet-receipt/{transaction}', [PrintController::class, 'walletReceipt'])->name('admin.print.wallet-receipt');
    Route::get('/print/contribution-receipt/{contribution}', [PrintController::class, 'contributionReceipt'])->name('admin.print.contribution-receipt');
    Route::get('/print/utility-receipt/{transaction}', [PrintController::class, 'utilityReceipt'])->name('admin.print.utility-receipt');
});

// Admin template downloads
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/templates/members-template.xlsx', [TemplateDownloadController::class, 'members'])->name('admin.templates.members');
    Route::get('/templates/loans-template.xlsx', [TemplateDownloadController::class, 'loans'])->name('admin.templates.loans');
    Route::get('/templates/member-balance-template.xlsx', [TemplateDownloadController::class, 'memberBalance'])->name('admin.templates.member-balance');
    Route::get('/templates/migration-users.xlsx', [TemplateDownloadController::class, 'migrationUsers'])->name('admin.templates.migration-users');
    Route::get('/templates/migration-balances.xlsx', [TemplateDownloadController::class, 'migrationBalances'])->name('admin.templates.migration-balances');
    Route::get('/templates/migration-loans.xlsx', [TemplateDownloadController::class, 'migrationLoans'])->name('admin.templates.migration-loans');
});
