<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getSubheading(): ?string
    {
        return 'Overview of cooperative activities, financial metrics, and system performance.';
    }
}
