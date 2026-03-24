<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;

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
