<?php

namespace App\Console\Commands;

use App\Services\ChatService;
use Illuminate\Console\Command;

class ChatExpireSensitiveFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:expire-sensitive-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire sensitive files in chat after 48 hours';

    /**
     * Execute the console command.
     */
    public function handle(ChatService $chatService)
    {
        $this->info('Starting expiry of sensitive files...');
        $chatService->expireSensitiveFiles();
        $this->info('Expiry completed.');
    }
}
