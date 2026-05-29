<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseEntryResource\Pages;
use App\Models\ExpenseEntry;
use App\Models\TransactionApproval;
use App\Models\User;
use App\Models\Vendor;
use App\Services\PayoutService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ExpenseEntryResource extends Resource
{
    protected static ?string $model = ExpenseEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?string $navigationLabel = 'Expenses & Payouts';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Details')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->native(false),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('category')
                            ->maxLength(255)
                            ->placeholder('e.g., Office, Utilities, Transport'),
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('₦'),
                        Forms\Components\Select::make('source_of_funds')
                            ->options([
                                'administrative_fund' => 'Administrative Fund (Monthly Fees)',
                                'investment_fund' => 'Investment Fund',
                            ])
                            ->default('administrative_fund')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull()
                            ->rows(3),
                    ])->columns(2),

                Forms\Components\Section::make('Vendor & Payout Details')
                    ->description('Fill this if you want to process an automated payout via the payment gateway.')
                    ->schema([
                        Forms\Components\Select::make('recipient_type')
                            ->options([
                                'vendor' => 'Vendor',
                                'member' => 'Member',
                                'other' => 'Other / Outsider',
                            ])
                            ->default('vendor')
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('vendor_id', null);
                                $set('member_id', null);
                            }),
                        Forms\Components\Select::make('vendor_id')
                            ->relationship('vendor', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn (Forms\Get $get) => $get('recipient_type') === 'vendor')
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $vendor = Vendor::find($state);
                                    if ($vendor) {
                                        $set('bank_name', $vendor->settlement_bank_name);
                                        $set('bank_code', $vendor->settlement_bank_code);
                                        $set('account_number', $vendor->settlement_account_number);
                                        $set('account_name', $vendor->settlement_account_name);
                                    }
                                }
                            }),
                        Forms\Components\Select::make('member_id')
                            ->label('Member')
                            ->relationship('member', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name . " ({$record->membership_number})")
                            ->searchable(['surname', 'name', 'membership_number'])
                            ->preload()
                            ->live()
                            ->visible(fn (Forms\Get $get) => $get('recipient_type') === 'member')
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $user = User::find($state);
                                    if ($user) {
                                        $set('bank_name', $user->bank_name);
                                        $set('bank_code', $user->bank_code);
                                        $set('account_number', $user->account_number);
                                        $set('account_name', $user->account_name);
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('bank_name'),
                        Forms\Components\TextInput::make('bank_code')
                            ->helperText('CBN Bank Code'),
                        Forms\Components\TextInput::make('account_number')
                            ->length(10)
                            ->live(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('account_name')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Verify to fetch name')
                                    ->columnSpan(1),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('verify_account')
                                        ->label('Verify Account')
                                        ->icon('heroicon-o-check-badge')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            $accountNumber = $get('account_number');
                                            $bankCode = $get('bank_code');

                                            if (!$accountNumber || !$bankCode) {
                                                Notification::make()->title('Enter account number and bank code')->warning()->send();
                                                return;
                                            }

                                            try {
                                                $name = PayoutService::resolveAccountNumber($accountNumber, $bankCode);
                                                if ($name) {
                                                    $set('account_name', $name);
                                                    Notification::make()->title('Account Verified: ' . $name)->success()->send();
                                                } else {
                                                    Notification::make()->title('Account not found')->danger()->send();
                                                }
                                            } catch (\Exception $e) {
                                                Notification::make()->title('Verification failed')->body($e->getMessage())->danger()->send();
                                            }
                                        })
                                ])->columnSpan(1)->alignCenter(),
                            ]),
                    ])->columns(2),

                Forms\Components\Section::make('Proof of Expenditure')
                    ->schema([
                        Forms\Components\FileUpload::make('receipt_path')
                            ->label('Invoice/Receipt Upload')
                            ->image()
                            ->directory('expense-receipts')
                            ->required()
                            ->helperText('Mandatory for all expenses.'),
                    ]),
            ])->columns(2);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Expense Details')
                    ->schema([
                        TextEntry::make('date')->date(),
                        TextEntry::make('title'),
                        TextEntry::make('category'),
                        TextEntry::make('amount')->money('ngn'),
                        TextEntry::make('source_of_funds')->label('Source of Funds')->badge(),
                        TextEntry::make('creator.full_name')->label('Entered By'),
                        TextEntry::make('status')->badge()
                            ->colors([
                                'warning' => 'pending',
                                'info' => 'approved',
                                'success' => 'processed',
                                'danger' => 'rejected',
                            ]),
                        TextEntry::make('notes')->columnSpanFull(),
                    ])->columns(2),

                InfoSection::make('Payout Information')
                    ->schema([
                        TextEntry::make('recipient_type')->badge()->formatStateUsing(fn ($state) => ucfirst($state)),
                        TextEntry::make('vendor.name')->label('Vendor')->visible(fn (ExpenseEntry $record) => $record->recipient_type === 'vendor'),
                        TextEntry::make('member.full_name')->label('Member')->visible(fn (ExpenseEntry $record) => $record->recipient_type === 'member'),
                        TextEntry::make('bank_name'),
                        TextEntry::make('account_number'),
                        TextEntry::make('account_name'),
                        TextEntry::make('payout_reference')
                            ->label('Payment Reference')
                            ->copyable()
                            ->visible(fn (ExpenseEntry $record) => $record->payout_reference),
                        TextEntry::make('transfer_code')
                            ->label('Transfer Code')
                            ->visible(fn (ExpenseEntry $record) => $record->transfer_code),
                        TextEntry::make('processed_at')
                            ->label('Processed At')
                            ->dateTime()
                            ->visible(fn (ExpenseEntry $record) => $record->processed_at),
                    ])->columns(2)->visible(fn (ExpenseEntry $record) => $record->account_number),

                InfoSection::make('Proof of Expenditure')
                    ->schema([
                        ImageEntry::make('receipt_path')
                            ->label('Receipt/Invoice')
                            ->size(400)
                            ->extraAttributes(['class' => 'cursor-zoom-in'])
                            ->url(fn($record) => $record->receipt_path ? Storage::url($record->receipt_path) : null, true),
                    ]),

                InfoSection::make('Multi-Sig Approvals')
                    ->schema([
                        RepeatableEntry::make('transactionApprovals')
                            ->label('Approvals Log')
                            ->schema([
                                TextEntry::make('approver.full_name')->label('Approver'),
                                TextEntry::make('status')->badge()->color('success'),
                                TextEntry::make('responded_at')->label('Signed At')->dateTime(),
                            ])->columns(3)
                    ])->visible(fn (ExpenseEntry $record) => $record->isHighValue()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->date('Y-m-d')->sortable(),
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('recipient')
                    ->getStateUsing(fn (ExpenseEntry $record) => match($record->recipient_type) {
                        'vendor' => $record->vendor?->name ?? 'Vendor (N/A)',
                        'member' => $record->member?->full_name ?? 'Member (N/A)',
                        default => 'Other / ' . ($record->account_name ?: 'Unknown'),
                    })
                    ->description(fn (ExpenseEntry $record) => $record->recipient_type === 'other' ? 'Outsider' : ucfirst($record->recipient_type))
                    ->searchable(['account_name']),
                TextColumn::make('category')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('amount')->money('ngn', true)->sortable(),
                TextColumn::make('status')->badge()
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'approved',
                        'success' => 'processed',
                        'danger' => 'rejected',
                    ]),
                TextColumn::make('source_of_funds')->label('Source')->badge()->toggleable(),
                TextColumn::make('approvals_count')
                    ->label('Admin Approvals')
                    ->getStateUsing(function (ExpenseEntry $record) {
                        if (!$record->isHighValue()) return 'N/A';
                        $count = $record->transactionApprovals()->where('status', 'approved')->count();
                        $required = config('cooperative.approvals.required_approvals_count', 2);
                        return "{$count} / {$required}";
                    })
                    ->badge()
                    ->color(fn (ExpenseEntry $record) => $record->isHighValue() ? ($record->hasSufficientApprovals() ? 'success' : 'warning') : 'gray')
                    ->toggleable(),
                ImageColumn::make('receipt_path')->label('Receipt')->circular()->toggleable(),
                TextColumn::make('creator.full_name')
                    ->label('Entered By')
                    ->searchable(['surname', 'name', 'other_names'])
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->since()->label('Created')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'processed' => 'Processed',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('source_of_funds')
                    ->options([
                        'administrative_fund' => 'Administrative Fund',
                        'investment_fund' => 'Investment Fund',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('sign')
                    ->label('Sign Approval')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->visible(fn (ExpenseEntry $record) =>
                        $record->status === 'pending' &&
                        auth()->user()->roles()->whereIn('name', ['Chairman', 'Treasurer', 'Sharia Auditor', 'super_admin'])->exists() &&
                        !$record->transactionApprovals()->where('approver_id', auth()->id())->exists()
                    )
                    ->requiresConfirmation()
                    ->action(function (ExpenseEntry $record) {
                        $record->transactionApprovals()->create([
                            'approver_id' => auth()->id(),
                            'role' => auth()->user()->getRoleNames()->first() ?? 'Admin',
                            'status' => 'approved',
                            'responded_at' => now(),
                        ]);

                        if (!$record->isHighValue() || $record->hasSufficientApprovals()) {
                             $record->update(['status' => 'approved']);
                        }

                        Notification::make()->title('Expense Signed/Approved')->success()->send();
                    }),
                Action::make('process_payout')
                    ->label('Process Payout')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (ExpenseEntry $record) =>
                        $record->status === 'approved' &&
                        $record->account_number &&
                        $record->receipt_path &&
                        auth()->user()->roles()->whereIn('name', ['Treasurer', 'super_admin'])->exists()
                    )
                    ->requiresConfirmation()
                    ->modalDescription('This will send real money via the payment gateway. Ensure account details are correct.')
                    ->action(function (ExpenseEntry $record) {
                        try {
                            $success = PayoutService::processExpensePayout($record);
                            if ($success) {
                                Notification::make()->title('Payout successful')->success()->send();
                            } else {
                                Notification::make()->title('Payout failed')->danger()->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ExpenseEntry $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->required()
                    ])
                    ->action(function (ExpenseEntry $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason']
                        ]);
                        Notification::make()->title('Expense Rejected')->danger()->send();
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(fn (ExpenseEntry $record) => $record->status === 'pending'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (ExpenseEntry $record) => $record->status === 'pending' && auth()->user()->roles()->where('name', 'super_admin')->exists()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_expense_entry');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_expense_entry');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_expense_entry');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_expense_entry');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->when(
                auth()->user()->roles()->where('name', 'Branch Manager')->exists(),
                fn ($query) => $query->whereHas('creator', fn ($q) => $q->where('branch_id', auth()->user()->branch_id))
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenseEntries::route('/'),
            'create' => Pages\CreateExpenseEntry::route('/create'),
            'edit' => Pages\EditExpenseEntry::route('/{record}/edit'),
        ];
    }
}
