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
            'loan_credit_score_enabled' => (bool) Setting::get('loan_credit_score_enabled', true),
            'required_loan_meetings' => (int) Setting::get('required_loan_meetings', config('cooperative.attendance.required_loan_meetings', 8)),
            'nursing_mother_grace_period_months' => (int) Setting::get('nursing_mother_grace_period_months', 3),
            'wallet_maintenance_charge_percentage' => Setting::get('wallet_maintenance_charge_percentage', config('cooperative.wallet.maintenance_charge.percentage')),
            'wallet_maintenance_charge_max' => Setting::get('wallet_maintenance_charge_max', config('cooperative.wallet.maintenance_charge.max_amount')),
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
                Section::make('Loan Settings')
                    ->description('Manage loan-related policy settings.')
                    ->schema([
                        Toggle::make('loan_credit_score_enabled')
                            ->label('Enable Credit Score for Loans')
                            ->helperText('If disabled, the Coop credit score will not be used to determine loan eligibility boost or guarantor requirements.')
                            ->default(true),
                        TextInput::make('required_loan_meetings')
                            ->label('Required Meeting Attendance')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->helperText('The minimum number of meetings a member must attend to be eligible for loan approval (e.g., 8). Admins can still approve manually if below this.'),
                    ]),
                Section::make('Grace Period Settings')
                    ->description('Manage grace periods for members.')
                    ->schema([
                        TextInput::make('nursing_mother_grace_period_months')
                            ->label('Nursing Mother Grace Period (Months)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->helperText('The number of months a nursing mother is exempt from attendance fines after childbirth or approval.'),
                    ]),
                Section::make('Wallet Settings')
                    ->description('Manage wallet maintenance and transaction charges.')
                    ->schema([
                        TextInput::make('wallet_maintenance_charge_percentage')
                            ->label('Maintenance Charge Percentage (%)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->helperText('Percentage of the top-up amount charged as system maintenance fee.'),
                        TextInput::make('wallet_maintenance_charge_max')
                            ->label('Maximum Maintenance Charge (NGN)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('₦')
                            ->helperText('The maintenance charge will be capped at this amount.'),
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
