<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CharityEntryResource\Pages;
use App\Models\CharityEntry;
use App\Models\ShariahAuditLog as ShariahAudit;
use App\Models\TransactionApproval;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Actions\HeaderAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class CharityEntryResource extends Resource
{
    protected static ?string $model = CharityEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Financials';

    protected static ?string $label = 'Charity Ledger';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->searchable(['surname', 'name', 'other_names'])
                            ->preload()
                            ->placeholder('General / Anonymous if null'),
                Forms\Components\TextInput::make('source')
                    ->required()
                    ->placeholder('e.g. Loan Penalties, Non-Shariah Profit, Direct Donation'),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->prefix('₦'),
                Forms\Components\Textarea::make('note')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Charity Details')
                    ->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('user.full_name')->label('Member'),
                        TextEntry::make('source'),
                        TextEntry::make('amount')->money('ngn'),
                        TextEntry::make('status')->badge()
                            ->colors([
                                'warning' => 'pending',
                                'success' => 'processed',
                            ]),
                        TextEntry::make('note')->columnSpanFull(),
                    ])->columns(2),
                InfoSection::make('Multi-Sig Approvals')
                    ->schema([
                        RepeatableEntry::make('transactionApprovals')
                            ->label('Approvals Log')
                            ->schema([
                                TextEntry::make('approver.name')->label('Approver'),
                                TextEntry::make('status')->badge()->color('success'),
                                TextEntry::make('responded_at')->label('Signed At')->dateTime(),
                            ])->columns(3)
                    ])->visible(fn (CharityEntry $record) => $record->isHighValue())
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('user.full_name')
                    ->label('Member')
                    ->searchable(['surname', 'name', 'other_names']),
                TextColumn::make('source')->searchable(),
                TextColumn::make('amount')
                    ->money('ngn', true)
                    ->sortable()
                    ->summarize(Sum::make()->money('ngn', true)->label('Net Balance')),
                TextColumn::make('status')->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'processed',
                    ]),
                TextColumn::make('approvals_count')
                    ->label('Admin Approvals')
                    ->getStateUsing(function (CharityEntry $record) {
                        if (!$record->isHighValue()) return 'N/A';
                        $count = $record->transactionApprovals()->where('status', 'approved')->count();
                        $required = config('cooperative.approvals.required_approvals_count', 2);
                        return "{$count} / {$required}";
                    })
                    ->badge()
                    ->color(fn (CharityEntry $record) => $record->isHighValue() ? ($record->hasSufficientApprovals() ? 'success' : 'warning') : 'gray')
                    ->toggleable(),
                TextColumn::make('note')->limit(50),
            ])
            ->headerActions([
                Tables\Actions\Action::make('disburse')
                    ->label('Disburse to Needy')
                    ->icon('heroicon-o-gift')
                    ->color('warning')
                    ->form([
                        Select::make('recipient_user_id')
                            ->label('Recipient Member (Optional)')
                            ->relationship('user', 'name', function (Builder $query) {
                                return $query->orderByRaw("EXISTS (SELECT 1 FROM user_badges WHERE user_badges.user_id = users.id AND badge_type = 'zakat_needy') DESC")
                                             ->orderBy('surname');
                            })
                            ->getOptionLabelFromRecordUsing(fn (User $record) => $record->full_name . ($record->badges()->where('badge_type', 'zakat_needy')->exists() ? ' ⭐ (Zakat Eligible)' : ''))
                            ->searchable(['surname', 'name', 'other_names'])
                            ->preload()
                            ->helperText('Select the member receiving this disbursement. Starred members are verified Zakat eligible.'),
                        Select::make('source')
                            ->options([
                                'Zakat Distribution' => 'Zakat Distribution',
                                'Zakat Al-Fitr Distribution' => 'Zakat Al-Fitr Distribution',
                                'Sadaqah/Charity Disbursement' => 'Sadaqah/Charity Disbursement',
                            ])
                            ->required()
                            ->default('Zakat Distribution'),
                        TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->prefix('₦')
                            ->helperText('Enter the amount to disburse (will be stored as negative)'),
                        Textarea::make('note')
                            ->placeholder('e.g. Distributed to needy member for medical bills')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $entry = CharityEntry::create([
                            'user_id' => $data['recipient_user_id'] ?? null,
                            'source' => $data['source'],
                            'amount' => -abs($data['amount']),
                            'note' => $data['note'],
                            'status' => 'pending', // High-value disburse needs approval
                        ]);

                        ShariahAudit::log(auth()->user(), 'charity_disbursement_created', [
                            'user_id' => $data['recipient_user_id'] ?? null,
                            'source' => $data['source'],
                            'amount' => -abs($data['amount']),
                        ]);

                        if ($entry->isHighValue()) {
                            Notification::make()
                                ->title('High-value disbursement created')
                                ->body('This disbursement requires Multi-Sig approval before being processed.')
                                ->warning()
                                ->send();
                        } else {
                            // auto-process small ones if desired, or keep as pending.
                            // For consistency, let's keep all as pending but allow instant process for small ones.
                        }
                    })
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'Zakat' => 'Zakat',
                        'Zakat Al-Fitr' => 'Zakat Al-Fitr',
                        'Zakat Distribution' => 'Zakat Distribution',
                        'Zakat Al-Fitr Distribution' => 'Zakat Al-Fitr Distribution',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (CharityEntry $record) => $record->status === 'pending')
                    ->after(function ($record) {
                        ShariahAudit::log(auth()->user(), 'charity_entry_updated', [
                            'id' => $record->id,
                            'source' => $record->source,
                            'amount' => $record->amount,
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (CharityEntry $record) => $record->status === 'pending')
                    ->before(function ($record) {
                        ShariahAudit::log(auth()->user(), 'charity_entry_deleted', [
                            'id' => $record->id,
                            'source' => $record->source,
                            'amount' => $record->amount,
                        ]);
                    }),
                Tables\Actions\Action::make('sign')
                    ->label('Sign Approval')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->visible(fn (CharityEntry $record) =>
                        $record->isHighValue() &&
                        $record->status === 'pending' &&
                        auth()->user()->hasAnyRole(['Chairman', 'Sharia Auditor']) &&
                        !$record->transactionApprovals()->where('approver_id', auth()->id())->exists()
                    )
                    ->requiresConfirmation()
                    ->action(function (CharityEntry $record) {
                        $record->transactionApprovals()->create([
                            'approver_id' => auth()->id(),
                            'role' => auth()->user()->roles->first()?->name ?? 'Admin',
                            'status' => 'approved',
                            'responded_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Disbursement signature recorded')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('process')
                    ->label('Process Disbursement')
                    ->icon('heroicon-o-check-badge')
                    ->color('primary')
                    ->visible(fn (CharityEntry $record) =>
                        $record->status === 'pending' &&
                        auth()->user()->can('update_charity_entry')
                    )
                    ->requiresConfirmation()
                    ->action(function (CharityEntry $record) {
                        if ($record->isAwaitingApprovals()) {
                            Notification::make()
                                ->title('Multi-Sig Required')
                                ->body('This high-value disbursement requires more admin signatures before processing.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update([
                            'status' => 'processed',
                            'processed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Disbursement marked as processed')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_charity_entry');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_charity_entry');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_charity_entry');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_charity_entry');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->when(
                auth()->user()->hasRole('Branch Manager'),
                fn ($query) => $query->where(function ($q) {
                    $q->whereHas('user', fn ($uq) => $uq->where('branch_id', auth()->user()->branch_id))
                      ->orWhereNull('user_id');
                })
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCharityEntries::route('/'),
        ];
    }
}
