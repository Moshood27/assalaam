<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class BranchPerformanceMap extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static string $view = 'filament.pages.branch-performance-map';

    protected static ?string $navigationLabel = 'Branch Analytics Map';

    protected static ?string $title = 'Branch Performance Analytics';

    protected static ?string $navigationGroup = 'Analytics';

    public function getSubheading(): ?string
    {
        return 'Visualise branch locations and key performance metrics on a map.';
    }

    public Collection $branches;

    public function mount(): void
    {
        if (!static::canAccess()) {
            abort(403);
        }

        $this->branches = Branch::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function (Branch $branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'latitude' => (float) $branch->latitude,
                    'longitude' => (float) $branch->longitude,
                    'savings_rate' => $branch->savings_rate,
                    'default_rate' => $branch->default_rate,
                ];
            });
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        return $user->is_admin === true || $user->hasRole('super_admin');
    }
}
