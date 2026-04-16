<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorResource\Pages;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Coop Store';
    protected static ?int $navigationSort = 85;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vendor Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('owner_user_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('address')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Settlement & Commission')
                    ->schema([
                        Forms\Components\TextInput::make('commission_rate')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->default(5),
                        Forms\Components\TextInput::make('settlement_bank_name'),
                        Forms\Components\TextInput::make('settlement_bank_code'),
                        Forms\Components\TextInput::make('settlement_account_number'),
                        Forms\Components\TextInput::make('settlement_account_name'),
                    ])->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_approved')
                            ->label('Approved')
                            ->default(true),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderByRaw('LENGTH(name) ASC')->orderBy('name', 'asc');
            })
            ->columns([
                TextColumn::make('name')->searchable()->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query
                        ->orderByRaw("LENGTH(name) $direction")
                        ->orderBy("name", $direction);
                }),
                TextColumn::make('owner.name')->label('Owner')->searchable()->sortable(),
                TextColumn::make('phone')->searchable(),
                IconColumn::make('is_approved')->boolean()->label('Approved')->sortable(),
                ToggleColumn::make('is_active')->label('Active'),
                TextColumn::make('commission_rate')->label('Commission')->suffix('%')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved')->label('Approved'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Vendor $record) => !$record->is_approved)
                    ->action(function (Vendor $record) {
                        $record->update(['is_approved' => true]);

                        if ($record->owner) {
                            $record->owner->notifyMember(
                                'Vendor Approved',
                                "Your vendor application for '{$record->name}' has been approved. You can now start adding products to your store.",
                                ['vendor_id' => $record->id, 'type' => 'vendor_approved']
                            );
                        }

                        Notification::make()->title('Vendor Approved')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Mark Pending')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->visible(fn (Vendor $record) => $record->is_approved)
                    ->requiresConfirmation()
                    ->action(function (Vendor $record) {
                        $record->update(['is_approved' => false]);
                        Notification::make()->title('Vendor status updated to Pending')->info()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_all')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $records->each(function ($record) {
                                if (!$record->is_approved) {
                                    $record->update(['is_approved' => true]);

                                    if ($record->owner) {
                                        $record->owner->notifyMember(
                                            'Vendor Approved',
                                            "Your vendor application for '{$record->name}' has been approved.",
                                            ['vendor_id' => $record->id, 'type' => 'vendor_approved']
                                        );
                                    }
                                }
                            });
                            Notification::make()->title('Selected vendors approved')->success()->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }
}
