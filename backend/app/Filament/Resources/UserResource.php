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
use App\Models\Contribution;
use App\Models\MemberApplication;
use App\Services\ChatService;
use App\Filament\Resources\ChatRoomResource;
use App\Mail\NewMemberWelcome;
use App\Mail\MemberApplicationRejected;
use App\Services\AttendanceService;
use App\Services\PushService;
use App\Services\SmsService;
use App\Services\TakafulService;
use App\Services\AdministrativeChargeService;
use Barryvdh\DomPDF\Facade\Pdf;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

                Forms\Components\Tabs::make('User Details')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Personal & Contact')
                            ->schema([
                                Forms\Components\Section::make('Basic Personal Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')->label('First Name')->required()->maxLength(255),
                                        Forms\Components\TextInput::make('surname')->maxLength(255),
                                        Forms\Components\TextInput::make('other_names')->maxLength(255),
                                        Forms\Components\Select::make('gender')
                                            ->options([
                                                'male' => 'Male',
                                                'female' => 'Female',
                                            ]),
                                        Forms\Components\TextInput::make('native_place')->label('Native (State or Town of Origin)'),
                                        Forms\Components\DatePicker::make('dob')->label('Date of Birth'),
                                        Forms\Components\Select::make('marital_status')
                                            ->options([
                                                'single' => 'Single',
                                                'married' => 'Married',
                                                'divorced' => 'Divorced',
                                                'widow' => 'Widow',
                                            ]),
                                        Forms\Components\TextInput::make('occupation'),
                                        Forms\Components\FileUpload::make('passport_path')
                                            ->label('Passport / Profile Photo')
                                            ->image()
                                            ->disk('public')
                                            ->directory('upload')
                                            ->visibility('public')
                                            ->fetchFileInformation(false)
                                            ->getUploadedFileUsing(function (BaseFileUpload $component, string $file, string|array|null $storedFileNames) {
                                                $path = ltrim((string) $file, '/');

                                                // Handle legacy public/upload paths
                                                if (str_starts_with($path, 'upload/') && !Storage::disk('public')->exists($path)) {
                                                    $fullPublicPath = public_path($path);
                                                    if (is_file($fullPublicPath)) {
                                                        return [
                                                            'name' => basename($path),
                                                            'size' => filesize($fullPublicPath),
                                                            'type' => null,
                                                            'url' => '/' . $path,
                                                        ];
                                                    }
                                                }

                                                return [
                                                    'name' => basename($path),
                                                    'size' => 0,
                                                    'type' => null,
                                                    'url' => Storage::disk('public')->url($path),
                                                ];
                                            })
                                            ->imageEditor()
                                            ->downloadable()
                                            ->openable(),
                                    ])->columns(3),

                                Forms\Components\Section::make('Contact Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Primary Phone')
                                            ->tel()
                                            ->maxLength(20),
                                        Forms\Components\TextInput::make('secondary_phone')
                                            ->label('Secondary Phone')
                                            ->tel()
                                            ->maxLength(20),
                                        Forms\Components\TextInput::make('address')
                                            ->label('Address')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Forms\Components\Textarea::make('residential_address')->rows(2),
                                        Forms\Components\Textarea::make('permanent_address')->rows(2),
                                    ])->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make('Identity & Membership')
                            ->schema([
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
                                        Select::make('roles')
                                            ->relationship('roles', 'name')
                                            ->multiple()
                                            ->preload()
                                            ->searchable(),
                                        Forms\Components\Toggle::make('is_defaulter')
                                            ->label('Defaulter')
                                            ->helperText('Restricts certain features for the member'),
                                        Forms\Components\FileUpload::make('id_card_path')->label('ID Card')->image()->disk('public')->directory('upload'),
                                        Forms\Components\FileUpload::make('proof_of_address_path')->label('Proof of Address')->image()->disk('public')->directory('upload'),
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
                                            ->default(0)
                                            ->readOnly(),
                                        Forms\Components\TextInput::make('outstanding_fines')
                                            ->label('Outstanding Fines')
                                            ->numeric()
                                            ->prefix('₦')
                                            ->default(0)
                                            ->readOnly()
                                            ->helperText('Total pending lateness and absence fines'),
                                        Forms\Components\DatePicker::make('created_at')
                                            ->label('Date Joined')
                                            ->displayFormat('Y-m-d')
                                            ->maxDate(now())
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
                                            ->required(fn ($livewire) => $livewire instanceof Pages\CreateUser)
                                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                                            ->dehydrated(fn ($state) => filled($state))
                                            ->maxLength(255),
                                    ])->columns(3),

                                Forms\Components\Section::make('Bank Details')
                                    ->schema([
                                        Forms\Components\TextInput::make('bank_name')->label('Bank Name')->maxLength(120)->disabled(),
                                        Forms\Components\TextInput::make('bank_code')->label('Bank Code')->maxLength(20)->disabled(),
                                        Forms\Components\TextInput::make('account_number')->label('Account Number')->password()->revealable(fn () => auth()->user()->hasRole('super_admin'))->maxLength(20)->disabled(),
                                        Forms\Components\TextInput::make('account_name')->label('Account Name (Verified)')->maxLength(255)->disabled(),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Takaful & Zakat')
                            ->schema([
                                Forms\Components\Section::make('Takaful & Notifications')
                                    ->schema([
                                        Forms\Components\Toggle::make('takaful_exempt')->label('Exempt from Takaful charges'),
                                        Forms\Components\Toggle::make('takaful_notify_contacts')->label('Notify guarantors/next-of-kin on settlement')->default(true),
                                        Forms\Components\Group::make([
                                            Forms\Components\Toggle::make('notify_email')->label('Email Notifications')->default(true),
                                            Forms\Components\Toggle::make('notify_sms')->label('SMS Notifications')->default(true),
                                            Forms\Components\Toggle::make('notify_push')->label('Push Notifications')->default(true),
                                        ])->columns(3)->columnSpanFull(),
                                        Forms\Components\DateTimePicker::make('deceased_at')->label('Deceased At')->native(false)->seconds(false),
                                        Forms\Components\DateTimePicker::make('major_loss_at')->label('Major Loss At')->native(false)->seconds(false),
                                    ])->columns(2),

                                Forms\Components\Section::make('Zakat Information')
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('zakat_nisab_crossed_at')->label('Nisab Crossed At')->native(false),
                                        Forms\Components\DateTimePicker::make('zakat_last_paid_at')->label('Last Zakat Paid At')->native(false),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Business & Kin')
                            ->schema([
                                Forms\Components\Section::make('Business & Professional Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('nature_of_business'),
                                        Forms\Components\Textarea::make('business_address')->rows(2),
                                        Forms\Components\Toggle::make('has_other_cooperatives')->label('Other Cooperative Affiliations'),
                                        Forms\Components\Textarea::make('other_cooperative_details')
                                            ->visible(fn (Get $get) => $get('has_other_cooperatives'))
                                            ->rows(2),
                                    ])->columns(2),

                                Forms\Components\Section::make('Next of Kin')
                                    ->schema([
                                        Forms\Components\TextInput::make('nok_name')->label('Next of Kin Name'),
                                        Forms\Components\TextInput::make('nok_phone')->label('Next of Kin Phone'),
                                        Forms\Components\TextInput::make('nok_relationship')->label('Relationship'),
                                        Forms\Components\Textarea::make('nok_address')->label('Next of Kin Address')->rows(2),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Guarantor & Religious')
                            ->schema([
                                Forms\Components\Section::make('Guarantor Details')
                                    ->schema([
                                        Forms\Components\TextInput::make('guarantor_name'),
                                        Forms\Components\TextInput::make('guarantor_phone'),
                                        Forms\Components\TextInput::make('guarantor_occupation'),
                                        Forms\Components\Textarea::make('guarantor_address')->rows(2),
                                        Forms\Components\FileUpload::make('guarantor_signature_path')->label('Guarantor Signature')->image()->disk('public_root')->directory('upload'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Religious Information & Imam\'s Attestation')
                                    ->schema([
                                        Forms\Components\TextInput::make('religious_society_name'),
                                        Forms\Components\TextInput::make('imam_name')->label('Imam/Amir Name'),
                                        Forms\Components\TextInput::make('imam_phone')->label('Imam/Amir Phone'),
                                        Forms\Components\TextInput::make('duration_of_jamma_membership'),
                                        Forms\Components\Textarea::make('mosque_address')->rows(2),
                                        Forms\Components\Toggle::make('imam_approval_status')->label('Imam\'s Approval Status'),
                                        Forms\Components\DateTimePicker::make('imam_approved_at'),
                                        Forms\Components\FileUpload::make('imam_signature_path')->label('Imam Signature')->image()->disk('public_root')->directory('upload'),
                                    ])->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make('Female Members & Official')
                            ->schema([
                                Forms\Components\Section::make('Information for Female Members (Wali/Spouse Details)')
                                    ->schema([
                                        Forms\Components\TextInput::make('spouse_father_name')->label('Father/Spouse Name'),
                                        Forms\Components\TextInput::make('spouse_father_phone')->label('Father/Spouse Phone'),
                                        Forms\Components\Textarea::make('spouse_father_address')->label('Residential Address')->rows(2),
                                        Forms\Components\Textarea::make('spouse_father_business_address')->label('Business Address')->rows(2),
                                        Forms\Components\FileUpload::make('spouse_father_consent_signature_path')->label('Consent Signature')->image()->disk('public_root')->directory('upload'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Nursing Mother Grace (Admin Verified)')
                                    ->schema([
                                        Forms\Components\Select::make('nursing_mother_status')
                                            ->options([
                                                'pending' => 'Pending Request',
                                                'approved' => 'Approved',
                                                'rejected' => 'Rejected',
                                            ])
                                            ->label('Request Status'),
                                        Forms\Components\DateTimePicker::make('nursing_mother_grace_until')
                                            ->label('Grace Period Ends At')
                                            ->helperText('Approved members are exempt from attendance fines until this date.'),
                                        Forms\Components\FileUpload::make('nursing_mother_proof_path')
                                            ->label('Medical Proof / Scan')
                                            ->disk('public')
                                            ->directory('nursing_mother_proofs')
                                            ->downloadable()
                                            ->openable()
                                            ->columnSpanFull(),
                                        Forms\Components\Toggle::make('is_nursing_mother')
                                            ->label('Currently Pregnant/Nursing (Legacy Toggle)')
                                            ->helperText('Manual override; ideally use the date above.'),
                                        Forms\Components\DatePicker::make('baby_birth_date')
                                            ->label('Baby Birth Date')
                                            ->helperText('Grace applies for ' . \App\Models\Setting::get('nursing_mother_grace_period_months', 3) . ' months from this date.'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Official Use Only')
                                    ->schema([
                                        Forms\Components\TextInput::make('admission_form_number'),
                                        Forms\Components\DatePicker::make('admission_date'),
                                        Forms\Components\TextInput::make('admission_officer_name'),
                                        Forms\Components\Textarea::make('officer_recommendation')->rows(2),
                                        Forms\Components\Select::make('approval_status')
                                            ->options([
                                                'pending' => 'Pending',
                                                'recommended' => 'Recommended',
                                                'approved' => 'Approved',
                                                'rejected' => 'Rejected',
                                            ])
                                            ->required()
                                            ->default('approved'),
                                        Forms\Components\FileUpload::make('president_signature_path')->label('President Signature')->image()->disk('public_root')->directory('upload'),
                                        Forms\Components\DateTimePicker::make('president_signed_at'),
                                        Forms\Components\FileUpload::make('secretary_general_signature_path')->label('Secretary General Signature')->image()->disk('public_root')->directory('upload'),
                                        Forms\Components\DateTimePicker::make('secretary_general_signed_at'),
                                    ])->columns(3),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderByRaw('LENGTH(surname) ASC')->orderBy('surname', 'asc');
            })
            ->columns([
                ImageColumn::make('passport_path')
                    ->label('Photo')
                    ->circular()
                    ->disk('public_root')
                    ->getStateUsing(function ($record) {
                        if (empty($record->passport_path)) {
                            return null;
                        }

                        $raw = (string) $record->passport_path;

                        // If it's already a full URL, return it
                        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
                            return $raw;
                        }

                        $path = ltrim($raw, '/');

                        // If it exists in public/, return it as-is (relative to public_root disk)
                        if (is_file(public_path($path))) {
                            return $path;
                        }

                        // If it starts with storage/, we need to resolve it via the public disk
                        if (str_starts_with($path, 'storage/')) {
                            $storagePath = substr($path, strlen('storage/'));
                            return Storage::disk('public')->url($storagePath);
                        }

                        // Fallback to public disk URL
                        return Storage::disk('public')->url($path);
                    })
                    ->size(40)
                    ->extraImgAttributes([
                        'class' => 'transition-transform hover:scale-[5] hover:z-50 hover:relative hover:rounded-none',
                        'style' => 'cursor: pointer;',
                    ])
                    ->extraAttributes([
                        'class' => 'overflow-visible',
                    ]),
                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['name', 'surname', 'other_names'])
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderBy("surname", $direction)
                            ->orderBy("name", $direction)
                            ->orderBy("other_names", $direction);
                    }),
                TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('approval_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'recommended' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('phone')->label('Phone')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('address')->label('Address')->limit(30)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('branch.name')->label('Branch')->sortable()->searchable(),
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
                TextColumn::make('outstanding_fines')
                    ->label('Fines')
                    ->money('ngn', true)
                    ->sortable()
                    ->color('danger'),
                TextColumn::make('gold_balance')
                    ->label('Gold Balance')
                    ->suffix(' g')
                    ->sortable()
                    ->numeric(6),
                TextColumn::make('created_at')->label('Date Joined')->date(),
                TextColumn::make('meeting_attendance_count')
                    ->label('Attendance')
                    ->getStateUsing(fn (User $record) => $record->meetingAttendanceCount())
                    ->sortable(false),
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
                        return (int) abs(now()->diffInDays($record->zakat_nisab_crossed_at)) >= $lunarDays;
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
                Tables\Filters\SelectFilter::make('branch')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Branch'),
                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label('Admin Status')
                    ->placeholder('All Users')
                    ->trueLabel('Admins')
                    ->falseLabel('Non-Admins'),
                Tables\Filters\Filter::make('has_qard_hasan')
                    ->label('Has Qard Hasan Loan')
                    ->query(fn (Builder $query) => $query->whereHas('qardHasans')),
                Tables\Filters\SelectFilter::make('approval_status')
                    ->options([
                        'pending' => 'Pending',
                        'recommended' => 'Recommended',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->label('Approval Status'),
                Tables\Filters\SelectFilter::make('nursing_mother_status')
                    ->options([
                        'pending' => 'Pending Request',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->label('Nursing Mother Request Status'),
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
                Action::make('print_list')
                    ->label('Print Member List')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Table $table) => route('admin.print.users-list', [
                        'branch_id' => data_get($table->getLivewire()->tableFilters, 'branch.value'),
                        'search' => $table->getLivewire()->getTableSearch(),
                    ]))
                    ->openUrlInNewTab(),
                Action::make('print')
                    ->label('Print Screen')
                    ->icon('heroicon-o-computer-desktop')
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
                Action::make('printInfo')
                    ->label('Print Info')
                    ->icon('heroicon-o-printer')
                    ->action(function (User $record) {
                        $pdf = Pdf::loadView('pdfs.bulk_membership_applications', ['users' => [$record]])->setPaper('a4');
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, Str::replace(['/', '\\'], '_', "member-{$record->membership_number}.pdf"));
                    }),
                Tables\Actions\EditAction::make(),
                Action::make('chat')
                    ->label('Chat')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info')
                    ->action(function (User $record, ChatService $chatService) {
                        $room = $chatService->getOrCreatePrivateRoom(auth()->user(), $record);
                        return redirect(ChatRoomResource::getUrl('chat', ['record' => $room]));
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->hasRole('super_admin')), // Only visible to Super Admin
                Action::make('creditWallet')
                    ->label('Credit Wallet')
                    ->icon('heroicon-o-banknotes')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount to credit')
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->prefix('₦'),
                        Forms\Components\TextInput::make('note')
                            ->label('Note')
                            ->maxLength(255)
                            ->placeholder('Optional reason'),
                    ])
                    ->action(function (User $record, array $data) {
                        try {
                            $amount = (float) ($data['amount'] ?? 0);
                            $result = app(AdministrativeChargeService::class)->applyManualTransaction($record, $amount, 'credit', $data['note'] ?? null);

                            $actualAmount = $result['actual_amount'];
                            $maintenanceCharge = $result['maintenance_charge'];
                            $adminChargeDeducted = $result['admin_charge_deducted'];
                            $newBalance = $result['new_balance'];

                            Notification::make()
                                ->title('Wallet credited successfully')
                                ->body("Principal: ₦" . number_format($amount, 2) .
                                      ($maintenanceCharge > 0 ? ". Maintenance charge of ₦" . number_format($maintenanceCharge, 2) . " deducted." : "") .
                                      ($adminChargeDeducted > 0 ? ". Pending admin charge of ₦" . number_format($adminChargeDeducted, 2) . " was also deducted." : ""))
                                ->success()
                                ->send();

                            DB::afterCommit(function () use ($record, $amount, $actualAmount, $maintenanceCharge, $adminChargeDeducted, $data, $newBalance) {
                                ShariahAudit::log(auth()->user(), 'credit_wallet_manual', [
                                    'user_id' => $record->id,
                                    'gross_amount' => $amount,
                                    'actual_credit' => $actualAmount,
                                    'maintenance_charge' => $maintenanceCharge,
                                    'admin_charge_deducted' => $adminChargeDeducted,
                                    'note' => $data['note'] ?? null,
                                    'new_balance' => $newBalance,
                                ]);

                                $msg = "Your wallet has been credited with ₦" . number_format($actualAmount, 2);
                                if ($maintenanceCharge > 0) {
                                    $msg .= " after a maintenance charge of ₦" . number_format($maintenanceCharge, 2);
                                }
                                if ($adminChargeDeducted > 0) {
                                    $msg .= ". An outstanding administrative charge of ₦" . number_format($adminChargeDeducted, 2) . " was also deducted.";
                                }
                                $msg .= ". New balance: ₦" . number_format($newBalance, 2);

                                $record->notifyMember(
                                    'Wallet Credited',
                                    $msg,
                                    [
                                        'type' => 'wallet_credit',
                                        'gross_amount' => (float) $amount,
                                        'actual_amount' => (float) $actualAmount,
                                        'maintenance_charge' => (float) $maintenanceCharge,
                                        'admin_charge_deducted' => (float) $adminChargeDeducted,
                                        'balance' => (float) $newBalance,
                                    ]
                                );
                            });
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Action failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
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
                        try {
                            $amount = (float) ($data['amount'] ?? 0);
                            $result = app(AdministrativeChargeService::class)->applyManualTransaction($record, $amount, 'debit', $data['note'] ?? null);

                            $actualAmount = $result['actual_amount'];
                            $maintenanceCharge = $result['maintenance_charge'];
                            $adminChargeDeducted = $result['admin_charge_deducted'];
                            $newBalance = $result['new_balance'];

                            Notification::make()
                                ->title('Wallet debited successfully')
                                ->body("Principal: ₦" . number_format($amount, 2) .
                                      ($maintenanceCharge > 0 ? ". Maintenance charge of ₦" . number_format($maintenanceCharge, 2) . " added to debit." : "") .
                                      ($adminChargeDeducted > 0 ? ". Pending admin charge of ₦" . number_format($adminChargeDeducted, 2) . " was also deducted." : ""))
                                ->success()
                                ->send();

                            DB::afterCommit(function () use ($record, $amount, $actualAmount, $maintenanceCharge, $adminChargeDeducted, $data, $newBalance) {
                                ShariahAudit::log(auth()->user(), 'debit_wallet_manual', [
                                    'user_id' => $record->id,
                                    'gross_amount' => $amount,
                                    'actual_debit' => $actualAmount,
                                    'maintenance_charge' => $maintenanceCharge,
                                    'admin_charge_deducted' => $adminChargeDeducted,
                                    'note' => $data['note'] ?? null,
                                    'new_balance' => $newBalance,
                                ]);

                                $msg = "Your wallet has been debited by ₦" . number_format($actualAmount, 2);
                                if ($maintenanceCharge > 0) {
                                    $msg .= " (includes ₦" . number_format($maintenanceCharge, 2) . " maintenance charge)";
                                }
                                if ($adminChargeDeducted > 0) {
                                    $msg .= ". An outstanding administrative charge of ₦" . number_format($adminChargeDeducted, 2) . " was also deducted.";
                                }
                                $msg .= ". New balance: ₦" . number_format($newBalance, 2);

                                $record->notifyMember(
                                    'Wallet Debited',
                                    $msg,
                                    [
                                        'type' => 'wallet_debit',
                                        'gross_amount' => (float) $amount,
                                        'actual_amount' => (float) $actualAmount,
                                        'maintenance_charge' => (float) $maintenanceCharge,
                                        'admin_charge_deducted' => (float) $adminChargeDeducted,
                                        'balance' => (float) $newBalance,
                                    ]
                                );
                            });
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Action failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
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
                Action::make('approveMember')
                    ->label('Approve Member')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (User $record) => $record->approval_status !== 'approved' && !$record->is_admin)
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->approval_status = 'approved';
                        if (empty($record->admission_date)) $record->admission_date = now();
                        if (empty($record->admission_officer_name)) $record->admission_officer_name = auth()->user()->name;
                        $record->save();

                        // Sync with application if exists
                        MemberApplication::where('email', $record->email)->update([
                            'approval_status' => 'approved',
                            'user_id' => $record->id,
                            'finalized_at' => now(),
                        ]);

                        ShariahAudit::log(auth()->user(), 'approve_member_manually', [
                            'user_id' => $record->id,
                            'email' => $record->email,
                        ]);

                        // Send welcome email
                        try {
                            Mail::to($record->email)->send(new NewMemberWelcome($record));
                            $record->notifyMember(
                                "Membership Approved",
                                "Assalāmu ‘alaykum {$record->name}, your membership has been approved. You can now log in to the app.",
                                ['type' => 'membership_approved']
                            );
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Failed to send welcome notification', ['error' => $e->getMessage()]);
                        }

                        Notification::make()
                            ->title('Member Approved Successfully')
                            ->success()
                            ->send();
                    }),
                Action::make('rejectMember')
                    ->label('Reject Member')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record) => $record->approval_status !== 'approved' && !$record->is_admin)
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for rejection')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->requiresConfirmation()
                    ->action(function (User $record, array $data) {
                        $record->approval_status = 'rejected';
                        $record->officer_recommendation = $data['reason'];
                        $record->save();

                        // Sync with application if exists
                        $application = MemberApplication::where('email', $record->email)->first();
                        if ($application) {
                            $application->update([
                                'approval_status' => 'rejected',
                                'officer_recommendation' => $data['reason'],
                                'finalized_at' => now(),
                            ]);
                        }

                        ShariahAudit::log(auth()->user(), 'reject_member_manually', [
                            'user_id' => $record->id,
                            'reason' => $data['reason'],
                        ]);

                        // Send rejection email
                        try {
                            Mail::to($record->email)->send(new MemberApplicationRejected($application ?? $record, $data['reason']));
                            $record->notifyMember(
                                "Membership Application Rejected",
                                "Regrettably, your membership application has been rejected. Reason: " . $data['reason'],
                                ['type' => 'membership_rejected']
                            );
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Failed to send rejection notification', ['error' => $e->getMessage()]);
                        }

                        Notification::make()
                            ->title('Member Rejected')
                            ->danger()
                            ->send();
                    }),
                Action::make('approveNursingMotherGrace')
                    ->label('Approve Nursing Mother Grace')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record) => $record->nursing_mother_status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $months = (int) \App\Models\Setting::get('nursing_mother_grace_period_months', 3);
                        $record->nursing_mother_status = 'approved';
                        $record->nursing_mother_grace_until = now()->addMonths($months);
                        $record->save();

                        $record->notifyMember(
                            "Nursing Mother Grace Approved",
                            "Assalāmu ‘alaykum, your nursing mother grace application has been approved. You are exempt from attendance fines until " . $record->nursing_mother_grace_until->toDateString() . ".",
                            ['type' => 'nursing_mother_grace_approved']
                        );

                        Notification::make()
                            ->title('Nursing Mother Grace Approved')
                            ->success()
                            ->send();
                    }),
                Action::make('rejectNursingMotherGrace')
                    ->label('Reject Nursing Mother Grace')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record) => $record->nursing_mother_status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                    ])
                    ->requiresConfirmation()
                    ->action(function (User $record, array $data) {
                        $record->nursing_mother_status = 'rejected';
                        $record->save();

                        $record->notifyMember(
                            "Nursing Mother Grace Application Rejected",
                            "Your nursing mother grace application was not approved. Reason: " . $data['reason'],
                            ['type' => 'nursing_mother_grace_rejected']
                        );

                        Notification::make()
                            ->title('Nursing Mother Grace Rejected')
                            ->danger()
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
                Action::make('downloadEnrolmentForm')
                    ->label('Download Enrolment Form')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->action(fn (User $record) => response()->streamDownload(function () use ($record) {
                        echo Pdf::loadView('pdfs.membership_application', ['application' => $record])->output();
                    }, "enrolment-form-{$record->id}.pdf")),
                Action::make('downloadImamAttestation')
                    ->label('Download Imam Attestation')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (User $record) => response()->streamDownload(function () use ($record) {
                        echo Pdf::loadView('pdfs.imam_attestation', ['application' => $record])->output();
                    }, "imam-attestation-{$record->id}.pdf")),
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
                Action::make('chargeFine')
                    ->label('Charge Manual Fine')
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Fine Amount')
                            ->numeric()
                            ->prefix('₦')
                            ->required(),
                        Forms\Components\TextInput::make('note')
                            ->label('Reason')
                            ->required()
                            ->placeholder('e.g. Conduct unbecoming'),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->increment('outstanding_fines', (float) $data['amount']);

                        ShariahAudit::log(auth()->user(), 'manual_fine_charged', [
                            'user_id' => $record->id,
                            'amount' => (float) $data['amount'],
                            'reason' => $data['note'],
                        ]);

                        Notification::make()
                            ->title('Fine charged successfully')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Action::make('payFines')
                    ->label('Record Fine Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (User $record) => (float)$record->outstanding_fines > 0)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount Paid')
                            ->numeric()
                            ->prefix('₦')
                            ->default(fn (User $record) => (float)$record->outstanding_fines)
                            ->required(),
                        Forms\Components\TextInput::make('note')
                            ->label('Note')
                            ->placeholder('e.g. Paid in cash at the office'),
                    ])
                    ->action(function (User $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $amount = (float) $data['amount'];

                            Contribution::create([
                                'user_id' => $record->id,
                                'amount' => $amount,
                                'category' => 'fine',
                                'status' => 'success',
                                'reference' => 'MANUAL_FINE_' . Str::random(8),
                            ]);

                            ShariahAudit::log(auth()->user(), 'manual_fine_payment_recorded', [
                                'user_id' => $record->id,
                                'amount' => $amount,
                                'note' => $data['note'] ?? '',
                            ]);
                        });

                        Notification::make()
                            ->title('Fine payment recorded')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Action::make('waiveFines')
                    ->label('Waive All Fines')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (User $record) => (float)$record->outstanding_fines > 0)
                    ->action(function (User $record) {
                        app(AttendanceService::class)->waiveAllFines($record);

                        ShariahAudit::log(auth()->user(), 'manual_fine_waiver', [
                            'user_id' => $record->id,
                            'waived_amount' => (float) $record->getOriginal('outstanding_fines'),
                        ]);

                        Notification::make()
                            ->title('Fines waived successfully')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printForms')
                        ->label('Print Enrolment Forms')
                        ->icon('heroicon-o-printer')
                        ->action(fn (\Illuminate\Support\Collection $records) => response()->streamDownload(function () use ($records) {
                            $sortedRecords = $records->sortBy('name', SORT_NATURAL);
                            echo Pdf::loadView('pdfs.bulk_membership_applications', ['users' => $sortedRecords])->setPaper('a4')->output();
                        }, "bulk-enrolment-forms.pdf")),
                    Tables\Actions\BulkAction::make('waiveFinesBulk')
                        ->label('Waive Fines')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $service = app(AttendanceService::class);
                            $count = 0;
                            foreach ($records as $record) {
                                if ((float)$record->outstanding_fines > 0) {
                                    $service->waiveAllFines($record);

                                    ShariahAudit::log(auth()->user(), 'bulk_fine_waiver', [
                                        'user_id' => $record->id,
                                        'waived_amount' => (float) $record->getOriginal('outstanding_fines'),
                                    ]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("Fines waived for {$count} members")
                                ->success()
                                ->send();
                        }),
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
            RelationManagers\ChatRoomsRelationManager::class,
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
