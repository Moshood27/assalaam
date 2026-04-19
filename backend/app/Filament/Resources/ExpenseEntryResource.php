<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseEntryResource\Pages;
use App\Models\ExpenseEntry;
use App\Models\TransactionApproval;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ExpenseEntryResource extends Resource
{
    protected static ?string $model = ExpenseEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?string $navigationLabel = 'Expenses';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->closeOnDateSelection()
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
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull()
                    ->rows(3),
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
                        TextEntry::make('creator.full_name')->label('Entered By'),
                        TextEntry::make('status')->badge()
                            ->colors([
                                'warning' => 'pending',
                                'success' => 'processed',
                            ]),
                        TextEntry::make('notes')->columnSpanFull(),
                    ])->columns(2),
                InfoSection::make('Multi-Sig Approvals')
                    ->schema([
                        RepeatableEntry::make('transactionApprovals')
                            ->label('Approvals Log')
                            ->schema([
                                TextEntry::make('approver.full_name')->label('Approver'),
                                TextEntry::make('status')->badge()->color('success'),
                                TextEntry::make('responded_at')->label('Signed At')->dateTime(),
                            ])->columns(3)
                    ])->visible(fn (ExpenseEntry $record) => $record->isHighValue())
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->date('Y-m-d')->sortable(),
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('category')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('amount')->money('ngn', true)->sortable(),
                TextColumn::make('status')->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'processed',
                    ]),
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
                TextColumn::make('creator.full_name')
                    ->label('Entered By')
                    ->searchable(['surname', 'name', 'other_names'])
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->since()->label('Created')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Minimal filters for now
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (ExpenseEntry $record) => $record->status === 'pending'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (ExpenseEntry $record) => $record->status === 'pending'),
                Action::make('sign')
                    ->label('Sign Approval')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->visible(fn (ExpenseEntry $record) =>
                        $record->isHighValue() &&
                        $record->status === 'pending' &&
                        auth()->user()->hasAnyRole(['Chairman', 'Sharia Auditor']) &&
                        !$record->transactionApprovals()->where('approver_id', auth()->id())->exists()
                    )
                    ->requiresConfirmation()
                    ->action(function (ExpenseEntry $record) {
                        $record->transactionApprovals()->create([
                            'approver_id' => auth()->id(),
                            'role' => auth()->user()->roles->first()?->name ?? 'Admin',
                            'status' => 'approved',
                            'responded_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Expense signature recorded')
                            ->success()
                            ->send();
                    }),
                Action::make('process')
                    ->label('Process Payment')
                    ->icon('heroicon-o-check-badge')
                    ->color('primary')
                    ->visible(fn (ExpenseEntry $record) =>
                        $record->status === 'pending' &&
                        auth()->user()->can('update_expense_entry')
                    )
                    ->requiresConfirmation()
                    ->action(function (ExpenseEntry $record) {
                        if ($record->isAwaitingApprovals()) {
                            Notification::make()
                                ->title('Multi-Sig Required')
                                ->body('This high-value expense requires more admin signatures before processing.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update([
                            'status' => 'processed',
                            'processed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Expense marked as processed')
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
                auth()->user()->hasRole('Branch Manager'),
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
