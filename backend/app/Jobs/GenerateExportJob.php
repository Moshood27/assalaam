<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\GeneralNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    public function __construct(
        public User $user,
        public string $type, // 'passbook', 'statement', etc.
        public array $params = [],
        public string $format = 'pdf'
    ) {}

    public function handle()
    {
        $filename = $this->generateFilename();
        $path = "exports/{$this->user->id}/{$filename}";

        try {
            $content = $this->generateContent();

            Storage::disk(config('filesystems.default'))->put($path, $content);

            $url = Storage::disk(config('filesystems.default'))->url($path);

            $this->user->notifyMember(
                "Export Ready",
                "Your " . ucfirst($this->type) . " export is ready for download.",
                [
                    'type' => 'export_ready',
                    'download_url' => $url,
                    'route' => $url,
                ]
            );

        } catch (\Throwable $e) {
            \Log::error("Export Job Failed: " . $e->getMessage(), [
                'user_id' => $this->user->id,
                'type' => $this->type
            ]);

            $this->user->notifyMember(
                "Export Failed",
                "We encountered an error while generating your " . ucfirst($this->type) . ". Please try again later.",
                ['type' => 'export_failed']
            );
        }
    }

    protected function generateFilename(): string
    {
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(5);
        return "{$this->type}_{$timestamp}_{$random}.{$this->format}";
    }

    protected function generateContent()
    {
        $service = app(\App\Services\ExportService::class);

        return match($this->type) {
            'passbook' => $service->generatePassbookPdf($this->user, (int)($this->params['year'] ?? now()->year)),
            'statement' => $service->generateStatementPdf($this->user, (int)($this->params['months'] ?? 6)),
            default => throw new \Exception("Unsupported export type: {$this->type}"),
        };
    }
}
