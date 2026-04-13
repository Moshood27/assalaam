<?php

namespace App\Filament\Resources;

use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Mail\WalletCredited;
use App\Jobs\SendBulkCommunication;
use App\Models\Branch;
use App\Models\ShariahAuditLog as ShariahAudit;
use App\Models\User;
use App\Services\PushService;
use App\Services\SmsService;
use App\Services\TakafulService;
use Filament\Forms;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('deceased_alert')
                    ->hidden(fn (User $record = null) => $record === null || $record->deceased_at === null)
                    ->content(function (User $record) {
                        return new \Illuminate\Support\HtmlString('<div class="p-4 bg-danger-500/10 text-danger-700 rounded-lg border border-danger-500/20"><strong>DECEASED:</strong> This member is marked as deceased. Please see the <strong>Wasiyyah (Beneficiaries)</strong> tab below for distribution instructions.</div>');
                    })
                    ->columnSpanFull(),
                Forms\Components\Section::make('Profile')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required()->maxLength(255),
                        Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(20)
                            ->helperText('Used for SMS notifications'),
                        Forms\Components\TextInput::make('address')
                            ->label('Address')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('passport_path')
                            ->label('Passport / Profile Photo')
                            ->image()
                            // Store directly in public/upload so it's accessible at /upload/... in dev and prod
                            ->disk('public_root')
                            ->directory('upload')
                            ->visibility('public')
                            // Don't let Filament filter out existing values just because they are on another disk
                            ->fetchFileInformation(false)
                            // Build a correct preview URL whether the file lives in public/ or storage/app/public
                            ->getUploadedFileUsing(function (BaseFileUpload $component, string $file, string|array|null $storedFileNames) {
                                $raw = (string) $file;
                                $path = ltrim($raw, '/');
                                $wasStoragePrefixed = false;
                                if (str_starts_with($path, 'storage/')) {
                                    $path = substr($path, strlen('storage/'));
                                    $wasStoragePrefixed = true;
                                }

                                $url = null;
                                $publicFull = public_path($path);
                                if (is_file($publicFull)) {
                                    $url = '/'.ltrim($path, '/');
                                } else {
                                    // If original value started with `storage/`, avoid creating `/storage/storage/...`
                                    $url = $wasStoragePrefixed
                                        ? ('/storage/'.ltrim($path, '/'))
                                        : Storage::disk('public')->url($path);

                                    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                                        $parsed = parse_url($url);
                                        $url = ($parsed['path'] ?? '/').(isset($parsed['query']) ? ('?'.$parsed['query']) : '');
                                    }
                                }

                                return [
                                    'name' => basename($path),
                                    'size' => 0,
                                    'type' => null,
                                    'url' => $url,
                                ];
                            })
                            ->imageEditor()
                            ->downloadable()
                            ->openable(),
                        Forms\Components\DatePicker::make('created_at')
                            ->label('Date Joined')
                            ->displayFormat('Y-m-d')
                            ->maxDate(now())
                            ->rule('before_or_equal:today')
                            ->dehydrateStateUsing(function ($state) {
                                if (empty($state)) {
                                    return null;
                                }
                                try {
                                    return Carbon::parse($state)->startOfDay();
                                } catch (\Throwable $e) {
                                    return $state;
                                }
                            }),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255),
                    ])->columns(3),
                Forms\Components\Section::make('Identity & KYC')
                    ->schema([
                        Forms\Components\TextInput::make('bvn')
                            ->label('BVN')
                            ->maxLength(11)
                            ->password()
                            ->revealable(fn () => auth()->user()->hasRole('super_admin')),
                        Forms\Components\DateTimePicker::make('bvn_verified_at')
                            ->label('BVN Verified At')
                            ->disabled(),
                        Forms\Components\Toggle::make('is_admin')
                            ->label('Administrator')
                            ->helperText('Grants access to this admin panel')
                            ->visible(fn () => auth()->user()->can('manage_admins')),
                        // Add this Role Selector
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple() // Allow a user to have multiple roles if needed
                            ->preload()
                            ->searchable(),
                        Forms\Components\Toggle::make('is_defaulter')
                            ->label('Defaulter')
                            ->helperText('Restricts certain features for the member'),
                    ])->columns(2),
                Forms\Components\Section::make('Membership')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->label('Branch')
                            ->options(Branch::query()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('membership_number')
                            ->password()
                            ->revealable(fn () => auth()->user()->hasRole('super_admin'))
                            ->maxLength(255)
                            ->rule(function (Get $get, ?User $record) {
                                $branchId = $get('branch_id');
                                $number = $get('membership_number');
                                if (blank($branchId) || blank($number)) {
                                    return null;
                                }
                                $rule = Rule::unique('users', 'membership_number')
                                    ->where(fn ($q) => $q->where('branch_id', $branchId));
                                if ($record) {
                                    $rule = $rule->ignore($record->id);
                                }

                                return $rule;
                            }),
                        Forms\Components\TextInput::make('balance')
                            ->numeric()
                            ->prefix('₦')
                            ->default(0),
                    ])->columns(3),
                Forms\Components\Section::make('Passbook Balances')
                    ->description('Combined balance for loan eligibility')
                    ->schema([
                        Forms\Components\TextInput::make('ordinary_savings')
                            ->numeric()
                            ->prefix('₦')
                            ->label('Ordinary Savings')
                            ->default(0),
                        Forms\Components\TextInput::make('shares_capital')
                            ->numeric()
                            ->prefix('₦')
                            ->label('Shares Capital')
                            ->default(0),
                        Forms\Components\Placeholder::make('passbook_total')
                            ->label('Passbook Total')
                            ->content(function ($record) {
                                if (!$record) return '₦0.00';
                                try {
                                    $savings = (float)($record->ordinary_savings ?? 0);
                                    $shares = (float)($record->shares_capital ?? 0);
                                    if ($savings == 0 && $shares == 0) {
                                        // Fallback if columns empty/missing
                                        $calc = $record->savingsSharesEligibility();
                                        return '₦' . number_format($calc['base'], 2);
                                    }
                                    return '₦' . number_format($savings + $shares, 2);
                                } catch (\Throwable $e) {
                                    return '₦0.00';
                                }
                            })
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Bank Details')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->maxLength(120)
                            ->disabled()
                            ->helperText('Set by member via mobile app after verification'),
                        Forms\Components\TextInput::make('bank_code')
                            ->label('Bank Code')
                            ->maxLength(20)
                            ->disabled(),
                        Forms\Components\TextInput::make('account_number')
                            ->label('Account Number')
                            ->password()
                            ->revealable(fn () => auth()->user()->hasRole('super_admin'))
                            ->maxLength(20)
                            ->disabled(),
                        Forms\Components\TextInput::make('account_name')
                            ->label('Account Name (Verified)')
                            ->maxLength(255)
                            ->disabled(),
                    ])->columns(2),
                Forms\Components\Section::make('Administrative Charges')
                    ->schema([
                        Forms\Components\TextInput::make('admin_charge_balance')
                            ->label('Outstanding Balance')
                            ->numeric()
                            ->prefix('₦')
                            ->default(0),
                        Forms\Components\Toggle::make('admin_charge_auto_deduct')
                            ->label('Auto-Deduct Monthly Charge')
                            ->default(true),
                        Forms\Components\DateTimePicker::make('last_admin_charge_at')
                            ->label('Last Processed At')
                            ->native(false)
                            ->readOnly(),
                    ])->columns(3),
                Forms\Components\Section::make('Takaful & Notifications')
                    ->schema([
                        Forms\Components\Toggle::make('takaful_exempt')->label('Exempt from Takaful charges'),
                        Forms\Components\Toggle::make('takaful_notify_contacts')->label('Notify guarantors/next-of-kin on settlement')->default(true),
                        Forms\Components\Group::make([
                            Forms\Components\Toggle::make('notify_email')->label('Email Notifications')->default(true),
                            Forms\Components\Toggle::make('notify_sms')->label('SMS Notifications')->default(true),
                            Forms\Components\Toggle::make('notify_push')->label('Push Notifications')->default(true),
                        ])->columns(3)->columnSpanFull(),
                        Forms\Components\Section::make('Zakat Information')
                            ->schema([
                                Forms\Components\DateTimePicker::make('zakat_nisab_crossed_at')
                                    ->label('Nisab Crossed At')
                                    ->helperText('The date when the user wealth first crossed the Nisab threshold.')
                                    ->native(false),
                                Forms\Components\DateTimePicker::make('zakat_last_paid_at')
                                    ->label('Last Zakat Paid At')
                                    ->native(false),
                            ])->columns(2),
                        Forms\Components\DateTimePicker::make('deceased_at')->label('Deceased At')->native(false)->seconds(false),
                        Forms\Components\DateTimePicker::make('major_loss_at')->label('Major Loss At')->native(false)->seconds(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('passport_path')
                    ->label('Photo')
                    ->circular()
                    ->getStateUsing(function ($record) {
                        if (empty($record->passport_path)) {
                            return null;
                        }

                        $raw = (string) $record->passport_path;
                        // If a full URL was stored, normalize to a relative URL (same-origin)
                        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
                            $parsed = parse_url($raw);

                            return ($parsed['path'] ?? '/').(isset($parsed['query']) ? ('?'.$parsed['query']) : '');
                        }

                        $path = ltrim($raw, '/');
                        $wasStoragePrefixed = false;
                        if (str_starts_with($path, 'storage/')) {
                            $path = substr($path, strlen('storage/'));
                            $wasStoragePrefixed = true;
                        }

                        $publicPath = public_path($path);
                        if (is_file($publicPath)) {
                            return '/'.ltrim($path, '/');
                        }

                        // Fallback to storage URL. If originally storage-prefixed, avoid double storage.
                        $url = $wasStoragePrefixed
                            ? ('/storage/'.ltrim($path, '/'))
                            : Storage::disk('public')->url($path);

                        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                            $parsed = parse_url($url);
                            $url = ($parsed['path'] ?? '/').(isset($parsed['query']) ? ('?'.$parsed['query']) : '');
                        }

                        return $url;
                    })
                    ->size(40),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('phone')->label('Phone')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('address')->label('Address')->limit(30)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('branch.name')->label('Branch')->sortable(),
                TextColumn::make('membership_number')
                    ->label('Member #')
                    ->searchable()
                    ->formatStateUsing(function ($state) {
                        if (auth()->user()->hasRole('super_admin')) {
                            return $state;
                        }

                        return Str::mask($state, '*', 2, -2);
                    }),
                Tables\Columns\IconColumn::make('is_admin')->label('Admin')->boolean()->sortable(),
                Tables\Columns\IconColumn::make('is_defaulter')->label('Defaulter')->boolean()->sortable()->color('danger'),
                Tables\Columns\IconColumn::make('bvn_verified_at')
                    ->label('KYC Verified')
                    ->boolean()
                    ->getStateUsing(fn (User $record) => $record->bvn_verified_at !== null)
                    ->sortable(),
                TextColumn::make('balance')->money('ngn', true)->sortable(),
                TextColumn::make('admin_charge_balance')
                    ->label('Admin Due')
                    ->money('ngn', true)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ordinary_savings')
                    ->label('Savings')
                    ->money('ngn', true)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('shares_capital')
                    ->label('Shares')
                    ->money('ngn', true)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gold_balance')
                    ->label('Gold Balance')
                    ->suffix(' g')
                    ->sortable()
                    ->numeric(6),
                TextColumn::make('created_at')->label('Date Joined')->date(),
                TextColumn::make('account_number')
                    ->label('Bank Acct #')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state) {
                        if (auth()->user()->hasRole('super_admin')) {
                            return $state;
                        }

                        return Str::mask($state, '*', 2, -2);
                    }),
                TextColumn::make('account_name')->label('Bank Acct Name')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_activity_at')
                    ->label('Last Activity')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('deceased_at')
                    ->label('Deceased')
                    ->boolean()
                    ->getStateUsing(fn (User $record) => $record->deceased_at !== null)
                    ->color('danger')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('zakat_nisab_crossed_at')
                    ->label('Nisab Crossed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('zakat_last_paid_at')
                    ->label('Last Zakat')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('zakat_eligible')
                    ->label('Zakat Due')
                    ->boolean()
                    ->getStateUsing(function (User $record) {
                        if (!$record->zakat_nisab_crossed_at) return false;
                        $lunarDays = (int) config('zakat.lunar_days', 354);
                        return now()->diffInDays($record->zakat_nisab_crossed_at) >= $lunarDays;
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        $lunarDays = (int) config('zakat.lunar_days', 354);
                        return $query->orderBy(
                            DB::raw('DATEDIFF(NOW(), zakat_nisab_crossed_at) >= ' . $lunarDays),
                            $direction
                        );
                    }),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('deceased')
                    ->label('Deceased Status')
                    ->placeholder('All Users')
                    ->trueLabel('Deceased Only')
                    ->falseLabel('Active Only')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('deceased_at'),
                        false: fn (Builder $query) => $query->whereNull('deceased_at'),
                    ),
                Tables\Filters\TernaryFilter::make('zakat_due')
                    ->label('Zakat Due Status')
                    ->placeholder('All Users')
                    ->trueLabel('Zakat Due')
                    ->falseLabel('Not Due')
                    ->queries(
                        true: function (Builder $query) {
                            $lunarDays = (int) config('zakat.lunar_days', 354);
                            return $query->whereNotNull('zakat_nisab_crossed_at')
                                ->whereRaw('DATEDIFF(NOW(), zakat_nisab_crossed_at) >= ?', [$lunarDays]);
                        },
                        false: function (Builder $query) {
                            $lunarDays = (int) config('zakat.lunar_days', 354);
                            return $query->where(function ($q) use ($lunarDays) {
                                $q->whereNull('zakat_nisab_crossed_at')
                                    ->orWhereRaw('DATEDIFF(NOW(), zakat_nisab_crossed_at) < ?', [$lunarDays]);
                            });
                        },
                    ),
                Tables\Filters\Filter::make('needs_wellness_check')
                    ->label('Needs Wellness Check')
                    ->query(function (Builder $query) {
                        $months = config('cooperative.legacy.inactivity_months', 6);
                        $threshold = now()->subMonths($months);
                        return $query->whereNull('deceased_at')
                            ->where(function($q) use ($threshold) {
                                $q->where('last_activity_at', '<', $threshold)
                                  ->orWhereNull('last_activity_at');
                            })
                            ->where(function($q) {
                                $q->whereNull('wellness_check_notified_at')
                                  ->orWhereColumn('wellness_check_notified_at', '<', 'last_activity_at');
                            });
                    }),
            ])
            ->headerActions([
                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->extraAttributes(['onclick' => 'window.print()']),
                Action::make('bulkCommunicate')
                    ->label('Bulk Communicate')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->form([
                        Forms\Components\Select::make('branch_id')
                            ->label('Branch')
                            ->options(Branch::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('title')
                            ->label('Title (Optional)')
                            ->placeholder('Coop Notice')
                            ->maxLength(100),
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\CheckboxList::make('channels')
                            ->label('Channels')
                            ->options([
                                'sms' => 'SMS',
                                'push' => 'Push Notification',
                                'mail' => 'Email',
                            ])
                            ->required()
                            ->columns(3),
                    ])
                    ->action(function (array $data) {
                        SendBulkCommunication::dispatch(
                            (int) $data['branch_id'],
                            $data['title'] ?: 'Coop Notice',
                            $data['message'],
                            $data['channels'],
                            auth()->id()
                        );

                        Notification::make()
                            ->title('Bulk communication queued.')
                            ->body("The messages are being sent in the background.")
                            ->info()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->hasRole('super_admin')), // Only visible to Super Admin
                Action::make('creditWallet')
                    ->label('Credit Wallet')
                    ->icon('heroicon-o-banknotes')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Gross Amount')
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->prefix('₦')
                            ->live(),
                        Forms\Components\Toggle::make('apply_maintenance_charge')
                            ->label('Apply System Maintenance Charge')
                            ->helperText('0.1% of amount, capped at 500 NGN')
                            ->default(false)
                            ->live(),
                        Forms\Components\Placeholder::make('charge_display')
                            ->label('Maintenance Charge')
                            ->content(function (Get $get) {
                                $amount = (float) $get('amount');
                                $apply = (bool) $get('apply_maintenance_charge');
                                if (!$apply || $amount <= 0) return '₦0.00';

                                $percentage = config('cooperative.wallet.maintenance_charge.percentage', 0.1) / 100;
                                $maxCharge = config('cooperative.wallet.maintenance_charge.max_amount', 500);
                                $charge = round(min($amount * $percentage, (float) $maxCharge), 2);

                                return '₦' . number_format($charge, 2);
                            })
                            ->visible(fn (Get $get) => (bool) $get('apply_maintenance_charge')),
                        Forms\Components\Placeholder::make('net_amount_display')
                            ->label('Net Credit to Wallet')
                            ->content(function (Get $get) {
                                $amount = (float) $get('amount');
                                $apply = (bool) $get('apply_maintenance_charge');
                                if ($amount <= 0) return '₦0.00';

                                if ($apply) {
                                    $percentage = config('cooperative.wallet.maintenance_charge.percentage', 0.1) / 100;
                                    $maxCharge = config('cooperative.wallet.maintenance_charge.max_amount', 500);
                                    $charge = round(min($amount * $percentage, (float) $maxCharge), 2);
                                    $net = max(0, $amount - $charge);
                                } else {
                                    $net = $amount;
                                }

                                return '₦' . number_format($net, 2);
                            })
                            ->extraAttributes(['class' => 'font-bold text-emerald-600']),
                        Forms\Components\TextInput::make('note')
                            ->label('Note')
                            ->maxLength(255)
                            ->placeholder('Optional reason'),
                    ])
                    ->action(function (User $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $grossAmount = (float) ($data['amount'] ?? 0);
                            if ($grossAmount <= 0) {
                                return;
                            }

                            $maintenanceCharge = 0;
                            if (!empty($data['apply_maintenance_charge'])) {
                                $percentage = config('cooperative.wallet.maintenance_charge.percentage', 0.1) / 100;
                                $maxCharge = config('cooperative.wallet.maintenance_charge.max_amount', 500);
                                $maintenanceCharge = round(min($grossAmount * $percentage, (float) $maxCharge), 2);
                            }

                            $netAmount = round(max(0, $grossAmount - $maintenanceCharge), 2);

                            $record->increment('balance', $netAmount);
                            $newBalance = (float) $record->fresh()->balance;

                            // Create a WalletTransaction record for transparency
                            \App\Models\WalletTransaction::create([
                                'user_id' => $record->id,
                                'type' => 'credit',
                                'amount' => $netAmount,
                                'reference' => 'MAN_'.strtoupper(bin2hex(random_bytes(4))),
                                'source' => 'manual_credit',
                                'meta' => [
                                    'gross_amount' => $grossAmount,
                                    'maintenance_charge' => $maintenanceCharge,
                                    'note' => $data['note'] ?? null,
                                    'admin_id' => auth()->id(),
                                ],
                            ]);

                            DB::afterCommit(function () use ($record, $netAmount, $maintenanceCharge, $data, $newBalance) {
                                ShariahAudit::log(auth()->user(), 'credit_wallet_manual', [
                                    'user_id' => $record->id,
                                    'gross_amount' => (float)($data['amount'] ?? 0),
                                    'maintenance_charge' => $maintenanceCharge,
                                    'net_amount' => $netAmount,
                                    'note' => $data['note'] ?? null,
                                    'new_balance' => $newBalance,
                                ]);

                                if ($record->notify_email && ! empty($record->email)) {
                                    try {
                                        // Update message to include charge if applied
                                        $msg = "Your wallet has been credited with ₦" . number_format($netAmount, 2);
                                        if ($maintenanceCharge > 0) {
                                            $msg .= " after a maintenance charge of ₦" . number_format($maintenanceCharge, 2);
                                        }
                                        $msg .= ".";
                                        if (!empty($data['note'])) {
                                            $msg .= " Note: " . $data['note'];
                                        }

                                        Mail::to($record->email)->send(new WalletCredited($record, $netAmount, $msg, $newBalance));
                                    } catch (\Throwable $e) {
                                        // Swallow email errors
                                    }
                                }
                                // Best-effort SMS notification
                                if ($record->notify_sms) {
                                    try {
                                        $sms = app(SmsService::class);
                                        $msg = 'Wallet credited: ₦'.number_format($netAmount, 2);
                                        if ($maintenanceCharge > 0) {
                                            $msg .= ' (Fee: ₦'.number_format($maintenanceCharge, 2).')';
                                        }
                                        $msg .= '. New bal: ₦'.number_format($newBalance, 2).'.';
                                        $sms->send($record->phone ?? null, $msg);
                                    } catch (\Throwable $e) {
                                        // ignore SMS errors
                                    }
                                }

                                // Best-effort Push notification
                                if ($record->notify_push) {
                                    try {
                                        $push = app(PushService::class);
                                        $token = $record->fcm_token ?: ($record->device_token ?? null);
                                        $push->send($token, 'Wallet Credited', 'Your wallet has been credited successfully.', [
                                            'type' => 'wallet_credit',
                                            'amount' => (float) $netAmount,
                                            'maintenance_charge' => (float) $maintenanceCharge,
                                            'balance' => (float) $newBalance,
                                        ]);
                                    } catch (\Throwable $e) {
                                        // ignore push errors
                                    }
                                }
                            });
                        });
                    })
                    ->color('success')
                    ->requiresConfirmation(),
                Action::make('debitWallet')
                    ->label('Debit Wallet')
                    ->icon('heroicon-o-minus-circle')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount to debit')
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->prefix('₦'),
                        Forms\Components\TextInput::make('note')
                            ->label('Note')
                            ->maxLength(255)
                            ->placeholder('Reason for manual debit'),
                    ])
                    ->action(function (User $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $amount = (float) ($data['amount'] ?? 0);
                            if ($amount <= 0) {
                                return;
                            }

                            if ((float) $record->balance < $amount) {
                                Notification::make()
                                    ->title('Insufficient balance')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $record->decrement('balance', $amount);
                            $newBalance = (float) $record->fresh()->balance;

                            DB::afterCommit(function () use ($record, $amount, $data, $newBalance) {
                                ShariahAudit::log(auth()->user(), 'debit_wallet_manual', [
                                    'user_id' => $record->id,
                                    'amount' => $amount,
                                    'note' => $data['note'] ?? null,
                                    'new_balance' => $newBalance,
                                ]);

                                // Best-effort notifications
                                if ($record->notify_sms) {
                                    try {
                                        $sms = app(SmsService::class);
                                        $msg = 'Wallet debited: ₦'.number_format($amount, 2).'. New bal: ₦'.number_format($newBalance, 2).'.';
                                        $sms->send($record->phone ?? null, $msg);
                                    } catch (\Throwable $e) {
                                    }
                                }

                                if ($record->notify_push) {
                                    try {
                                        $push = app(PushService::class);
                                        $token = $record->fcm_token ?: ($record->device_token ?? null);
                                        $push->send($token, 'Wallet Debited', 'Your wallet has been debited successfully.', [
                                            'type' => 'wallet_debit',
                                            'amount' => (float) $amount,
                                            'balance' => (float) $newBalance,
                                        ]);
                                    } catch (\Throwable $e) {
                                    }
                                }
                            });
                        });
                    })
                    ->color('danger')
                    ->requiresConfirmation(),
                Action::make('markDeceased')
                    ->label('Mark Deceased')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->form([
                        Forms\Components\DateTimePicker::make('date')->label('Date')->native(false)->seconds(false),
                    ])
                    ->requiresConfirmation()
                    ->action(function (User $record, array $data) {
                        $date = $data['date'] ?? null;
                        $record->deceased_at = $date ?: now();
                        $record->save();

                        ShariahAudit::log(auth()->user(), 'mark_member_deceased', [
                            'user_id' => $record->id,
                            'deceased_at' => $record->deceased_at,
                        ]);

                        $svc = app(TakafulService::class);
                        $summary = $svc->settleMemberLoans($record, 'deceased');
                        Notification::make()
                            ->title('Member marked deceased; settlement attempted')
                            ->body('Total settled: ₦'.number_format((float) ($summary['total_settled'] ?? 0), 2).'. Pool after: ₦'.number_format((float) ($summary['pool_after'] ?? 0), 2))
                            ->success()
                            ->send();
                    }),
                Action::make('markMajorLoss')
                    ->label('Mark Major Loss')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->form([
                        Forms\Components\DateTimePicker::make('date')->label('Date')->native(false)->seconds(false),
                    ])
                    ->requiresConfirmation()
                    ->action(function (User $record, array $data) {
                        $date = $data['date'] ?? null;
                        $record->major_loss_at = $date ?: now();
                        $record->save();

                        ShariahAudit::log(auth()->user(), 'mark_member_major_loss', [
                            'user_id' => $record->id,
                            'major_loss_at' => $record->major_loss_at,
                        ]);

                        $svc = app(TakafulService::class);
                        $summary = $svc->settleMemberLoans($record, 'major_loss');
                        Notification::make()
                            ->title('Member marked major loss; settlement attempted')
                            ->body('Total settled: ₦'.number_format((float) ($summary['total_settled'] ?? 0), 2).'. Pool after: ₦'.number_format((float) ($summary['pool_after'] ?? 0), 2))
                            ->success()
                            ->send();
                    }),
                Action::make('printPassbook')
                    ->label('Print Passbook')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('year')
                            ->options(array_combine(range(now()->year, now()->year - 5), range(now()->year, now()->year - 5)))
                            ->default(now()->year)
                            ->required(),
                    ])
                    ->url(fn (User $record, array $data) => route('admin.print.passbook', ['user' => $record->id, 'year' => $data['year'] ?? now()->year]))
                    ->openUrlInNewTab(),
                Action::make('reset2fa')
                    ->label('Reset 2FA')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (User $record) => $record->hasEnabledTwoFactor())
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->disableTwoFactorAuthentication();

                        ShariahAudit::log(auth()->user(), 'reset_user_2fa', [
                            'user_id' => $record->id,
                            'email' => $record->email,
                        ]);

                        Notification::make()
                            ->title('2FA Reset Successfully')
                            ->success()
                            ->send();
                    }),
                Action::make('markAsNeedy')
                    ->label('Mark as Zakat Eligible')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (User $record) => !$record->badges()->where('badge_type', 'zakat_needy')->exists())
                    ->action(function (User $record) {
                        $record->badges()->create([
                            'badge_type' => 'zakat_needy',
                            'name' => 'Zakat Eligible (Needy)',
                            'description' => 'This member has been verified as eligible for Zakat distribution within the cooperative.',
                            'earned_at' => now(),
                        ]);

                        ShariahAudit::log(auth()->user(), 'user_marked_zakat_needy', [
                            'user_id' => $record->id,
                        ]);

                        Notification::make()
                            ->title('Member marked as Zakat eligible')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Action::make('unmarkAsNeedy')
                    ->label('Remove Zakat Eligibility')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record) => $record->badges()->where('badge_type', 'zakat_needy')->exists())
                    ->action(function (User $record) {
                        $record->badges()->where('badge_type', 'zakat_needy')->delete();

                        ShariahAudit::log(auth()->user(), 'user_unmarked_zakat_needy', [
                            'user_id' => $record->id,
                        ]);

                        Notification::make()
                            ->title('Zakat eligibility removed')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Action::make('verifyKyc')
                    ->label('Verify KYC')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->visible(fn (User $record) => $record->bvn_verified_at === null)
                    ->form([
                        Forms\Components\TextInput::make('bvn')
                            ->label('BVN')
                            ->length(11)
                            ->numeric()
                            ->password()
                            ->revealable(fn () => auth()->user()->hasRole('super_admin'))
                            ->default(fn (User $record) => $record->bvn),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->bvn = $data['bvn'];
                        $record->bvn_verified_at = now();
                        $record->save();

                        ShariahAudit::log(auth()->user(), 'manual_kyc_verify', [
                            'user_id' => $record->id,
                            'bvn' => $record->bvn,
                        ]);

                        Notification::make()
                            ->title('KYC Verified')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('super_admin')), // Only visible to Super Admin
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_user');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_user');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_user');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_user');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        // If the user is a Super Admin, let them see everything
        if ($user->hasRole('super_admin')) {
            return parent::getEloquentQuery();
        }

        // Otherwise, only show records belonging to the user's branch
        return parent::getEloquentQuery()->where('branch_id', $user->branch_id);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QardHasansRelationManager::class,
            RelationManagers\QardHasanRepaymentsRelationManager::class,
            RelationManagers\ContributionsRelationManager::class,
            RelationManagers\TakafulContributionsRelationManager::class,
            RelationManagers\TakafulPoolEntriesRelationManager::class,
            RelationManagers\WithdrawalRequestsRelationManager::class,
            RelationManagers\SavingsGoalsRelationManager::class,
            RelationManagers\ProjectInvestmentsRelationManager::class,
            RelationManagers\ProjectProfitPayoutsRelationManager::class,
            RelationManagers\WalletTransactionsRelationManager::class,
            RelationManagers\StoreOrdersRelationManager::class,
            RelationManagers\BeneficiariesRelationManager::class,
            RelationManagers\JuniorAccountsRelationManager::class,
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
