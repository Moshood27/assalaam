<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Jobs\SendBulkCommunication;
use App\Models\Branch;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('latitude')
                    ->numeric()
                    ->step(0.00000001)
                    ->minValue(-90)
                    ->maxValue(90),
                Forms\Components\TextInput::make('longitude')
                    ->numeric()
                    ->step(0.00000001)
                    ->minValue(-180)
                    ->maxValue(180),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderByRaw('LENGTH(name) ASC')->orderBy('name', 'asc');
            })
            ->modifyQueryUsing(function (Builder $query) {
                // Preload members count for performance
                $query->withCount('users');
            })
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderByRaw("LENGTH(name) $direction")
                            ->orderBy("name", $direction);
                    }),
                TextColumn::make('latitude')
                    ->label('Lat')
                    ->sortable(),
                TextColumn::make('longitude')
                    ->label('Long')
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Total Members')
                    ->sortable(),
                TextColumn::make('total_savings')
                    ->label('Total Savings')
                    ->state(function (Branch $record): string {
                        // Sum all contributions for users in this branch
                        $total = $record->users()
                            ->withSum('contributions', 'amount')
                            ->get()
                            ->sum('contributions_sum_amount');

                        return number_format((float) $total, 2);
                    })
                    ->sortable(false),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('printAll')
                    ->label('Print All Branches')
                    ->icon('heroicon-o-printer')
                    ->extraAttributes(['onclick' => 'window.print()']),
            ])
            ->actions([
                Tables\Actions\Action::make('printMembers')
                    ->label('Print Members')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Branch $record) => route('admin.print.users-list', ['branch_id' => $record->id]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('communicate')
                    ->label('Bulk Communicate')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->form([
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
                            ])
                            ->required()
                            ->columns(2),
                    ])
                    ->action(function (Branch $record, array $data) {
                        SendBulkCommunication::dispatch(
                            $record->id,
                            $data['title'] ?: 'Coop Notice',
                            $data['message'],
                            $data['channels'],
                            auth()->id()
                        );

                        Notification::make()
                            ->title('Bulk communication queued for ' . $record->name)
                            ->body("The messages are being sent in the background.")
                            ->info()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_branch');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_branches');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('create_branches'); // Assuming create/manage branches permission
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_records');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
