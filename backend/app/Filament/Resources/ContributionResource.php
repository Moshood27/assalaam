<?php

namespace App\Filament\Resources;

use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\ContributionResource\Pages;
use App\Models\Contribution;
use App\Models\Project;
use App\Models\Scheme;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

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
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('scheme_id')
                            ->label('Scheme')
                            ->options(Scheme::query()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->rule('distinct'),
                        Forms\Components\Select::make('project_id')
                            ->label('Project (optional)')
                            ->options(Project::query()->where('active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->helperText('Link this payment to a pooled project (Mudarabah)')
                            ->columnSpan(1),
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
                Forms\Components\Select::make('project_id')
                    ->label('Project (optional)')
                    ->options(Project::query()->where('active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->native(false)
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
                TextColumn::make('user.membership_number')
                    ->label('Member #')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state) {
                        if (auth()->user()->hasRole('super_admin')) {
                            return $state;
                        }

                        return Str::mask($state, '*', 2, -2);
                    }),
                TextColumn::make('scheme.name')->label('Scheme')->searchable(),
                TextColumn::make('project.name')->label('Project')->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Actions\Action::make('printReceipt')
                    ->label('Print Receipt')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (Contribution $record) => route('admin.print.contribution-receipt', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Contribution $record) => $record->status === 'success'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('delete_records')),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_contribution');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_contribution');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_contribution');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_contribution');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                auth()->user()->hasRole('Branch Manager'),
                fn (Builder $query) => $query->whereHas('user', fn (Builder $q) => $q->where('branch_id', auth()->user()->branch_id))
            );
    }

    public static function getRelations(): array
    {
        return [
            ActivitiesRelationManager::class,
        ];
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
