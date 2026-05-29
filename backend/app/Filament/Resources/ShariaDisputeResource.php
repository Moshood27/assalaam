<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShariaDisputeResource\Pages;
use App\Filament\Resources\StoreOrderResource;
use App\Models\ShariaDispute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShariaDisputeResource extends Resource
{
    protected static ?string $model = ShariaDispute::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Sharia Board';

    protected static ?string $pluralLabel = 'Tahkim (Disputes)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dispute Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Member')
                            ->relationship('user', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->searchable(['surname', 'name', 'other_names'])
                            ->disabled(),
                        Forms\Components\Select::make('store_order_id')
                            ->relationship('order', 'reference')
                            ->label('Order Reference')
                            ->disabled(),
                        Forms\Components\Placeholder::make('member_email')
                            ->label('Member Email')
                            ->content(fn ($record): string => $record->user?->email ?? '-'),
                        Forms\Components\Placeholder::make('order_total')
                            ->label('Order Total')
                            ->content(fn ($record): string => $record->order ? 'â‚¦ ' . number_format($record->order->total_amount, 2) : '-'),
                        Forms\Components\TextInput::make('reason')
                            ->disabled(),
                        Forms\Components\Textarea::make('description')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Order Items')
                    ->schema([
                        Forms\Components\Repeater::make('orderItems')
                            ->schema([
                                Forms\Components\TextInput::make('product_name')
                                    ->label('Product')
                                    ->disabled(),
                                Forms\Components\TextInput::make('quantity')
                                    ->disabled(),
                                Forms\Components\TextInput::make('line_total')
                                    ->label('Total (â‚¦)')
                                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                                    ->disabled(),
                            ])
                            ->afterStateHydrated(function (Forms\Components\Repeater $component, ?ShariaDispute $record) {
                                if (!$record) return;
                                $component->state($record->orderItems->toArray());
                            })
                            ->dehydrated(false)
                            ->columns(3)
                            ->disabled()
                            ->deletable(false)
                            ->addable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ])
                    ->hidden(fn ($record) => !$record->order),

                Forms\Components\Section::make('Mediation (Tahkim)')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'mediation' => 'Under Mediation',
                                'resolved' => 'Resolved',
                                'rejected' => 'Rejected',
                            ])
                            ->required(),
                        Forms\Components\RichEditor::make('mediation_notes')
                            ->placeholder('Internal notes from the Sharia Board...')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('outcome_details')
                            ->placeholder('Final outcome shared with the member...')
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('resolved_at'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Member')
                    ->searchable(['surname', 'name', 'other_names'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.reference')
                    ->label('Order Ref')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reason')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'mediation' => 'warning',
                        'resolved' => 'success',
                        'rejected' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'mediation' => 'Mediation',
                        'resolved' => 'Resolved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_order')
                    ->label('View Order')
                    ->icon('heroicon-o-shopping-bag')
                    ->color('info')
                    ->url(fn ($record): string => $record->order ? StoreOrderResource::getUrl('edit', ['record' => $record->order]) : '#')
                    ->openUrlInNewTab()
                    ->hidden(fn ($record) => !$record->order),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShariaDisputes::route('/'),
            'edit' => Pages\EditShariaDispute::route('/{record}/edit'),
        ];
    }
}
