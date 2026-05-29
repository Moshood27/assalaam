<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Laravel\Pennant\Feature;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
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
            'gateway_paystack_enabled' => (bool) Setting::get('gateway_paystack_enabled', true),
            'gateway_flutterwave_enabled' => (bool) Setting::get('gateway_flutterwave_enabled', true),
            'gateway_monnify_enabled' => (bool) Setting::get('gateway_monnify_enabled', true),
            'gateway_opay_enabled' => (bool) Setting::get('gateway_opay_enabled', true),
            'primary_payment_gateway' => Setting::get('primary_payment_gateway', 'paystack'),
            'takaful_enabled' => Feature::for('global')->active('takaful-enabled'),
            'gold_savings_enabled' => Feature::for('global')->active('gold-savings-enabled'),
            'group_savings_enabled' => Feature::for('global')->active('group-savings-enabled'),
            'receive_qr_enabled' => Feature::for('global')->active('receive-qr-enabled'),
            'merchant_pay_enabled' => Feature::for('global')->active('merchant-pay-enabled'),
            'zakat_enabled' => Feature::for('global')->active('zakat-enabled'),
            'junior_coop_enabled' => Feature::for('global')->active('junior-coop-enabled'),
            'projects_enabled' => Feature::for('global')->active('projects-enabled'),
            'chat_help_enabled' => Feature::for('global')->active('chat-help-enabled'),
            'withdrawals_enabled' => Feature::for('global')->active('withdrawals-enabled'),
            'wellness_check_enabled' => (bool) Setting::get('wellness_check_enabled', true),
            'wellness_check_inactivity_months' => (int) Setting::get('wellness_check_inactivity_months', config('cooperative.legacy.inactivity_months', 6)),
            'wellness_check_period_days' => (int) Setting::get('wellness_check_period_days', config('cooperative.legacy.check_period_days', 30)),
            'loan_duration_rules' => json_decode(Setting::get('loan_duration_rules', '[]'), true) ?: [
                ['max_amount' => 1000000, 'duration' => 12],
                ['max_amount' => 2000000, 'duration' => 15],
                ['max_amount' => null, 'duration' => 18],
            ],
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
                        Repeater::make('loan_duration_rules')
                            ->label('Loan Duration Rules')
                            ->schema([
                                TextInput::make('max_amount')
                                    ->label('Maximum Amount (NGN)')
                                    ->numeric()
                                    ->prefix('â‚¦')
                                    ->placeholder('e.g. 1000000')
                                    ->helperText('Leave empty for "Above" (the catch-all rule)'),
                                TextInput::make('duration')
                                    ->label('Duration (Months)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => isset($state['max_amount'])
                                ? "Up to â‚¦" . number_format($state['max_amount']) . ": " . $state['duration'] . " months"
                                : "Above: " . ($state['duration'] ?? '?') . " months")
                            ->helperText('Define loan duration based on principal amount. Rules are evaluated in order.'),
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
                Section::make('Wellness Check Settings')
                    ->description('Manage wellness check notifications for inactive members.')
                    ->schema([
                        Toggle::make('wellness_check_enabled')
                            ->label('Enable Wellness Check')
                            ->helperText('If enabled, inactive members will receive wellness check notifications.'),
                        TextInput::make('wellness_check_inactivity_months')
                            ->label('Inactivity Threshold (Months)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->helperText('Number of months of inactivity before sending a wellness check.'),
                        TextInput::make('wellness_check_period_days')
                            ->label('Admin Alert Period (Days)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->helperText('Number of days to wait after notification before alerting admins if the member remains inactive.'),
                    ])->columns(3),
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
                            ->prefix('â‚¦')
                            ->helperText('The maintenance charge will be capped at this amount.'),
                    ]),
                Section::make('Payment Gateways')
                    ->description('Enable or disable payment gateways globally and select the primary provider. Disabling a gateway also hides its Dedicated Virtual Account (DVA) from members.')
                    ->schema([
                        Select::make('primary_payment_gateway')
                            ->label('Primary Payment Gateway')
                            ->options([
                                'paystack' => 'Paystack',
                                'flutterwave' => 'Flutterwave',
                                'monnify' => 'Monnify',
                                'opay' => 'Opay',
                            ])
                            ->required(),
                        Toggle::make('gateway_paystack_enabled')
                            ->label('Enable Paystack'),
                        Toggle::make('gateway_flutterwave_enabled')
                            ->label('Enable Flutterwave'),
                        Toggle::make('gateway_monnify_enabled')
                            ->label('Enable Monnify'),
                        Toggle::make('gateway_opay_enabled')
                            ->label('Enable Opay'),
                    ])->columns(2),
                Section::make('Dashboard Features')
                    ->description('Enable or disable specific features on the member dashboard.')
                    ->schema([
                        Toggle::make('takaful_enabled')->label('Takaful'),
                        Toggle::make('gold_savings_enabled')->label('Gold Savings'),
                        Toggle::make('group_savings_enabled')->label('Group Savings'),
                        Toggle::make('receive_qr_enabled')->label('Receive QR'),
                        Toggle::make('merchant_pay_enabled')->label('Merchant Pay'),
                        Toggle::make('zakat_enabled')->label('Zakat'),
                        Toggle::make('junior_coop_enabled')->label('Junior Coop'),
                        Toggle::make('projects_enabled')->label('Projects'),
                        Toggle::make('chat_help_enabled')->label('Chat & Help'),
                        Toggle::make('withdrawals_enabled')->label('Withdrawals Enabled')
                            ->helperText('Global kill switch for all withdrawals.'),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $features = [
            'takaful_enabled' => 'takaful-enabled',
            'gold_savings_enabled' => 'gold-savings-enabled',
            'group_savings_enabled' => 'group-savings-enabled',
            'receive_qr_enabled' => 'receive-qr-enabled',
            'merchant_pay_enabled' => 'merchant-pay-enabled',
            'zakat_enabled' => 'zakat-enabled',
            'junior_coop_enabled' => 'junior-coop-enabled',
            'projects_enabled' => 'projects-enabled',
            'chat_help_enabled' => 'chat-help-enabled',
            'withdrawals_enabled' => 'withdrawals-enabled',
        ];

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $features)) {
                if ($value) {
                    Feature::for('global')->activate($features[$key]);
                } else {
                    Feature::for('global')->deactivate($features[$key]);
                }
            } else {
                Setting::set($key, is_array($value) ? json_encode($value) : $value);
            }
        }

        Notification::make()
            ->success()
            ->title('Settings saved successfully.')
            ->send();
    }
}
