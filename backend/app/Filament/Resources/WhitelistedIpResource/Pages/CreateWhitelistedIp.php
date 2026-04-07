<?php

namespace App\Filament\Resources\WhitelistedIpResource\Pages;

use App\Filament\Resources\WhitelistedIpResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWhitelistedIp extends CreateRecord
{
    protected static string $resource = WhitelistedIpResource::class;
}
