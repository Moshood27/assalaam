<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContributionResource\Pages;
use App\Models\Contribution;
use App\Models\Scheme;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ContributionResource extends Resource
{
    protected static ?string $model = Contribution::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Member')
                    ->options(User::query()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                // For create: allow multiple schemes with amounts
                Forms\Components\Repeater::make('items')
                    ->label('Schemes & Amounts')
                    ->visibleOn('create')
                    ->required()
                    ->minItems(1)
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('scheme_id')
                            ->label('Scheme')
                            ->options(Scheme::query()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->rule('distinct'),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('₦')
                            ->required(),
                    ]),

                // For edit: keep single scheme and amount fields
                Forms\Components\Select::make('scheme_id')
                    ->label('Scheme')
                    ->options(Scheme::query()->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->hiddenOn('create'),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix('₦')
                    ->required()
                    ->hiddenOn('create'),

                Forms\Components\TextInput::make('reference')
                    ->maxLength(255)
                    ->hiddenOn('create'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ])->required(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Time')->since()->sortable(),
                TextColumn::make('user.name')->label('Member')->searchable(),
                TextColumn::make('scheme.name')->label('Scheme')->searchable(),
                TextColumn::make('amount')->money('ngn', true)->sortable(),
                TextColumn::make('status')->badge()->colors([
                    'success' => ['success', 'paid', 'completed'],
                    'warning' => ['pending', 'processing'],
                    'danger' => ['failed', 'cancelled', 'rejected'],
                    'gray' => [''],
                ]),
                TextColumn::make('reference')->label('Ref')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'paid' => 'Paid',
                        'completed' => 'Completed',
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->extraAttributes(['onclick' => 'window.print()']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListContributions::route('/'),
            'create' => Pages\CreateContribution::route('/create'),
            'edit' => Pages\EditContribution::route('/{record}/edit'),
        ];
    }
}
