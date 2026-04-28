<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanPenaltyResource\Pages;
use App\Models\LoanPenalty;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LoanPenaltyResource extends Resource
{
    protected static ?string $model = LoanPenalty::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Loans';

    protected static ?string $navigationLabel = 'Loan Penalties';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\Select::make('qard_hasan_id')
                    ->relationship('qardHasan', 'id')
                    ->required(),
                Forms\Components\TextInput::make('months_defaulted')
                    ->numeric()
                    ->default(0),
                Forms\Components\DateTimePicker::make('default_started_at'),
                Forms\Components\DateTimePicker::make('default_cleared_at'),
                Forms\Components\DateTimePicker::make('penalty_until'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Member')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.membership_number')
                    ->label('Membership #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.branch.name')
                    ->label('Branch')
                    ->sortable(),
                Tables\Columns\TextColumn::make('months_defaulted')
                    ->label('Months (Full)')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('formatted_default_duration')
                    ->label('Default Duration')
                    ->sortable(['default_started_at']),
                Tables\Columns\TextColumn::make('default_started_at')
                    ->label('Default Started')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('default_cleared_at')
                    ->label('Default Cleared')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('penalty_until')
                    ->label('Wait Until')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('formatted_wait_remaining')
                    ->label('Status / Remaining')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Expired' => 'gray',
                        'Pending Clear' => 'danger',
                        'Calculating...' => 'warning',
                        default => 'success',
                    })
                    ->sortable(['penalty_until']),
            ])
            ->filters([
                Tables\Filters\Filter::make('active_penalty')
                    ->query(fn (Builder $query): Builder => $query->where('penalty_until', '>', now()))
                    ->label('Active Penalties Only'),
                Tables\Filters\SelectFilter::make('branch')
                    ->relationship('user.branch', 'name')
                    ->label('Branch'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export_pdf')
                        ->label('Export to PDF (Selected)')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (Collection $records) {
                            $pdf = Pdf::loadView('reports.loan-penalties-pdf', [
                                'penalties' => $records->load(['user.branch']),
                                'branch' => null, // Multiple branches might be selected
                            ])->setPaper('a4', 'landscape');

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'loan-penalties-selected-' . now()->format('Y-m-d') . '.pdf'
                            );
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_pdf_all')
                    ->label('Export to PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (Table $table) {
                        $query = $table->getFilteredQuery();
                        $penalties = $query->with(['user.branch'])->get();

                        // Try to identify if a single branch is filtered
                        $branchId = $table->getFilter('branch')?->getState()['value'] ?? null;
                        $branch = $branchId ? Branch::find($branchId) : null;

                        $pdf = Pdf::loadView('reports.loan-penalties-pdf', [
                            'penalties' => $penalties,
                            'branch' => $branch,
                        ])->setPaper('a4', 'landscape');

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'loan-penalties-' . ($branch ? Str::slug($branch->name) : 'all') . '-' . now()->format('Y-m-d') . '.pdf'
                        );
                    }),
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
            'index' => Pages\ListLoanPenalties::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Automatically created by system
    }
}
