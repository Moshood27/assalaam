<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Coop Store';
    protected static ?int $navigationSort = 90;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('vendor_id')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Internal (Cooperative)'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('cost_price')
                    ->label('Cost Price (₦)')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('₦')
                    ->step('0.01'),
                Forms\Components\TextInput::make('markup_percent')
                    ->label('Markup %')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step('0.01')
                    ->default(10),
                Forms\Components\Group::make([
                    Forms\Components\Toggle::make('track_stock')
                        ->label('Track Stock')
                        ->live(),
                    Forms\Components\TextInput::make('stock_quantity')
                        ->label('Stock Quantity')
                        ->numeric()
                        ->default(0)
                        ->visible(fn ($get) => $get('track_stock')),
                ])->columns(2),
                Forms\Components\FileUpload::make('image_url')
                    ->label('Image')
                    ->image()
                    ->maxSize(1024) // KB
                    ->disk('public')
                    ->directory('products')
                    ->openable()
                    ->downloadable()
                    ->imagePreviewHeight('150')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                Forms\Components\Toggle::make('is_approved')
                    ->label('Approved')
                    ->disabled(fn () => ! (auth()->user()->is_admin || auth()->user()->hasAnyRole(['super_admin', 'Branch Manager'])))
                    ->default(true), // Admin-created products should be approved by default
                Forms\Components\Placeholder::make('selling_price_preview')
                    ->label('Selling Price (auto)')
                    ->content(fn ($record, $get) => (function () use ($record, $get) {
                        $cost = (float) ($get('cost_price') ?? ($record->cost_price ?? 0));
                        $pct = (float) ($get('markup_percent') ?? ($record->markup_percent ?? 0));
                        $sp = round($cost * (1 + max(0.0, $pct) / 100.0), 2);
                        return '₦ ' . number_format($sp, 2);
                    })()),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderByRaw('LENGTH(name) ASC')->orderBy('name', 'asc');
            })
            ->columns([
                Tables\Columns\TextColumn::make('category.name')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('vendor.name')->label('Vendor')->placeholder('Internal')->sortable()->toggleable(),
                TextColumn::make('name')->searchable()->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query
                        ->orderByRaw("LENGTH(name) $direction")
                        ->orderBy("name", $direction);
                })->wrap()->limit(40),
                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->color(fn ($record) => $record->track_stock && $record->stock_quantity <= 5 ? 'danger' : null)
                    ->icon(fn ($record) => $record->track_stock && $record->stock_quantity <= 5 ? 'heroicon-o-exclamation-triangle' : null),
                TextColumn::make('cost_price')->label('Cost')->money('ngn', true)->sortable(),
                TextColumn::make('markup_percent')->label('Markup %')->formatStateUsing(fn ($state) => number_format((float)$state, 2) . '%')->sortable(),
                TextColumn::make('selling_price')->label('Selling')->money('ngn', true)->sortable(),
                TextColumn::make('created_at')->since()->label('Created')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')->boolean()->label('Active')->alignCenter(),
                IconColumn::make('is_approved')->boolean()->label('Approved')->alignCenter(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active')
                    ->trueLabel('Active')->falseLabel('Inactive')->placeholder('All'),
                Tables\Filters\TernaryFilter::make('is_approved')->label('Approved')
                    ->trueLabel('Approved')->falseLabel('Pending')->placeholder('All'),
                Tables\Filters\Filter::make('low_stock')
                    ->toggle()
                    ->query(fn ($query) => $query->where('track_stock', true)->where('stock_quantity', '<=', 5)),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_approved && (auth()->user()->is_admin || auth()->user()->hasAnyRole(['super_admin', 'Branch Manager'])))
                    ->action(function ($record) {
                        $record->update([
                            'is_approved' => true,
                            'approved_at' => now(),
                            'approved_by_id' => auth()->id(),
                        ]);

                        if ($record->vendor && $record->vendor->owner) {
                            $record->vendor->owner->notifyMember(
                                'Product Approved',
                                "Your product '{$record->name}' has been approved and is now visible in the store.",
                                ['product_id' => $record->id, 'type' => 'product_approved']
                            );
                        }

                        Notification::make()
                            ->title('Product approved')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Mark Pending')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->visible(fn ($record) => $record->is_approved && (auth()->user()->is_admin || auth()->user()->hasAnyRole(['super_admin', 'Branch Manager'])))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_approved' => false]);

                        Notification::make()
                            ->title('Product marked as pending')
                            ->info()
                            ->send();
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
                        ->visible(fn () => auth()->user()->is_admin || auth()->user()->hasAnyRole(['super_admin', 'Branch Manager']))
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $records->each(function ($record) {
                                if (!$record->is_approved) {
                                    $record->update([
                                        'is_approved' => true,
                                        'approved_at' => now(),
                                        'approved_by_id' => auth()->id(),
                                    ]);

                                    if ($record->vendor && $record->vendor->owner) {
                                        $record->vendor->owner->notifyMember(
                                            'Product Approved',
                                            "Your product '{$record->name}' has been approved.",
                                            ['product_id' => $record->id, 'type' => 'product_approved']
                                        );
                                    }
                                }
                            });
                            Notification::make()
                                ->title('Selected products approved')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_product');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_product');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_product');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_product');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
