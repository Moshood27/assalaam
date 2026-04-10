<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CharityEntryResource\Pages;
use App\Models\CharityEntry;
use App\Models\ShariahAuditLog as ShariahAudit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Actions\HeaderAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
                    ->searchable()
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('user.name')->label('Member')->searchable(),
                TextColumn::make('source')->searchable(),
                TextColumn::make('amount')
                    ->money('ngn', true)
                    ->sortable()
                    ->summarize(Sum::make()->money('ngn', true)->label('Net Balance')),
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
                                             ->orderBy('name');
                            })
                            ->getOptionLabelFromRecordUsing(fn (User $record) => $record->name . ($record->badges()->where('badge_type', 'zakat_needy')->exists() ? ' ⭐ (Zakat Eligible)' : ''))
                            ->searchable()
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
                        CharityEntry::create([
                            'user_id' => $data['recipient_user_id'] ?? null,
                            'source' => $data['source'],
                            'amount' => -abs($data['amount']),
                            'note' => $data['note'],
                        ]);

                        ShariahAudit::log(auth()->user(), 'charity_disbursement_created', [
                            'user_id' => $data['recipient_user_id'] ?? null,
                            'source' => $data['source'],
                            'amount' => -abs($data['amount']),
                        ]);
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
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        ShariahAudit::log(auth()->user(), 'charity_entry_updated', [
                            'id' => $record->id,
                            'source' => $record->source,
                            'amount' => $record->amount,
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        ShariahAudit::log(auth()->user(), 'charity_entry_deleted', [
                            'id' => $record->id,
                            'source' => $record->source,
                            'amount' => $record->amount,
                        ]);
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
