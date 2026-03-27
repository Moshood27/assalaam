<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Branch;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Support\Facades\Mail;
use App\Mail\WalletCredited;
use App\Services\SmsService;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                            ->getUploadedFileUsing(function (\Filament\Forms\Components\BaseFileUpload $component, string $file, string|array|null $storedFileNames) {
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
                                    $url = '/' . ltrim($path, '/');
                                } else {
                                    // If original value started with `storage/`, avoid creating `/storage/storage/...`
                                    $url = $wasStoragePrefixed
                                        ? ('/storage/' . ltrim($path, '/'))
                                        : Storage::disk('public')->url($path);

                                    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                                        $parsed = parse_url($url);
                                        $url = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? ('?' . $parsed['query']) : '');
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
                                if (empty($state)) return null;
                                try {
                                    return \Illuminate\Support\Carbon::parse($state)->startOfDay();
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
                Forms\Components\Section::make('Membership')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->label('Branch')
                            ->options(Branch::query()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('membership_number')
                            ->maxLength(255)
                            ->rule(function (\Filament\Forms\Get $get, ?User $record) {
                                $branchId = $get('branch_id');
                                $number = $get('membership_number');
                                if (blank($branchId) || blank($number)) {
                                    return null;
                                }
                                $rule = \Illuminate\Validation\Rule::unique('users', 'membership_number')
                                    ->where(fn($q) => $q->where('branch_id', $branchId));
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
                        if (empty($record->passport_path)) return null;

                        $raw = (string) $record->passport_path;
                        // If a full URL was stored, normalize to a relative URL (same-origin)
                        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
                            $parsed = parse_url($raw);
                            return ($parsed['path'] ?? '/') . (isset($parsed['query']) ? ('?' . $parsed['query']) : '');
                        }

                        $path = ltrim($raw, '/');
                        $wasStoragePrefixed = false;
                        if (str_starts_with($path, 'storage/')) {
                            $path = substr($path, strlen('storage/'));
                            $wasStoragePrefixed = true;
                        }

                        $publicPath = public_path($path);
                        if (is_file($publicPath)) {
                            return '/' . ltrim($path, '/');
                        }

                        // Fallback to storage URL. If originally storage-prefixed, avoid double storage.
                        $url = $wasStoragePrefixed
                            ? ('/storage/' . ltrim($path, '/'))
                            : Storage::disk('public')->url($path);

                        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                            $parsed = parse_url($url);
                            $url = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? ('?' . $parsed['query']) : '');
                        }

                        return $url;
                    })
                    ->size(40),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('phone')->label('Phone')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('address')->label('Address')->limit(30)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('branch.name')->label('Branch')->sortable(),
                TextColumn::make('membership_number')->label('Member #')->searchable(),
                TextColumn::make('balance')->money('ngn', true)->sortable(),
                TextColumn::make('created_at')->label('Date Joined')->date(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->extraAttributes(['onclick' => 'window.print()']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
                        DB::transaction(function () use ($record, $data) {
                            $amount = (float) ($data['amount'] ?? 0);
                            if ($amount <= 0) {
                                return;
                            }

                            $record->increment('balance', $amount);
                            $newBalance = (float) $record->fresh()->balance;

                            DB::afterCommit(function () use ($record, $amount, $data, $newBalance) {
                                if (!empty($record->email)) {
                                    try {
                                        Mail::to($record->email)->send(new WalletCredited($record, $amount, $data['note'] ?? null, $newBalance));
                                    } catch (\Throwable $e) {
                                        // Swallow email errors to avoid blocking the admin action
                                    }
                                }
                                // Best-effort SMS notification
                                try {
                                    $sms = app(\App\Services\SmsService::class);
                                    $msg = 'Wallet credited: ₦'.number_format($amount, 2).". New bal: ₦".number_format($newBalance, 2).'.';
                                    $sms->send($record->phone ?? null, $msg);
                                } catch (\Throwable $e) {
                                    // ignore SMS errors
                                }

                                // Best-effort Push notification to the member's device
                                try {
                                    $push = app(\App\Services\PushService::class);
                                    $token = $record->fcm_token ?: ($record->device_token ?? null);
                                    $push->send($token, 'Wallet Credited', 'Your wallet has been credited successfully.', [
                                        'type' => 'wallet_credit',
                                        'amount' => (float) $amount,
                                        'balance' => (float) $newBalance,
                                    ]);
                                } catch (\Throwable $e) {
                                    // ignore push errors
                                }
                            });
                        });
                    })
                    ->color('success')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
