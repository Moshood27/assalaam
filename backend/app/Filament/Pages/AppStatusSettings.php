<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AppStatusSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'App Status';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.app-status-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'mobile_min_version' => Setting::get('mobile_min_version', config('cooperative.mobile_min_version')),
            'mobile_current_version' => Setting::get('mobile_current_version', config('cooperative.mobile_current_version')),
            'maintenance_mode' => (bool) Setting::get('maintenance_mode', config('cooperative.maintenance_mode')),
            'maintenance_message' => Setting::get('maintenance_message', config('cooperative.maintenance_message')),
            'maintenance_until' => Setting::get('maintenance_until', config('cooperative.maintenance_until')),
            'system_announcement' => Setting::get('system_announcement', config('cooperative.system_announcement')),
            'play_store_url' => Setting::get('play_store_url', config('cooperative.play_store_url')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Forced Update')
                    ->description('Manage minimum version requirements for native mobile apps.')
                    ->schema([
                        TextInput::make('mobile_min_version')
                            ->label('Minimum Mobile Version')
                            ->required()
                            ->helperText('Users on versions lower than this will be forced to update.'),
                        TextInput::make('mobile_current_version')
                            ->label('Latest Recommended Version')
                            ->required()
                            ->helperText('Users on older versions will see a non-blocking update prompt.'),
                        TextInput::make('play_store_url')
                            ->label('Play Store URL')
                            ->url()
                            ->required(),
                    ]),
                Section::make('Maintenance Mode')
                    ->description('Put the mobile app into maintenance mode.')
                    ->schema([
                        Toggle::make('maintenance_mode')
                            ->label('Enable Maintenance Mode'),
                        Textarea::make('maintenance_message')
                            ->label('Maintenance Message')
                            ->rows(3),
                        TextInput::make('maintenance_until')
                            ->label('Estimated Duration')
                            ->placeholder('e.g., Approximately 1 hour'),
                    ]),
                Section::make('Announcements')
                    ->description('Display a global announcement banner on the dashboard.')
                    ->schema([
                        Textarea::make('system_announcement')
                            ->label('Announcement Text')
                            ->rows(2)
                            ->helperText('Leave empty to hide the announcement.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        Notification::make()
            ->success()
            ->title('Settings saved successfully.')
            ->send();
    }
}
