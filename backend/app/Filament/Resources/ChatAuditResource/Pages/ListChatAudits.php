<?php

namespace App\Filament\Resources\ChatAuditResource\Pages;

use App\Filament\Resources\ChatAuditResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChatAudits extends ListRecords
{
    protected static string $resource = ChatAuditResource::class;
}
