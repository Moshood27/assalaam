<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class OnlineMembersWidget extends Widget
{
    protected static string $view = 'filament.widgets.online-members-widget';

    protected int | string | array $columnSpan = 'full';
}
