<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgmSessionResource\Pages;
use App\Filament\Resources\AgmSessionResource\RelationManagers\CandidatesRelationManager;
use App\Filament\Resources\AgmSessionResource\RelationManagers\VotesRelationManager;
use App\Models\AgmSession;
use App\Models\ShariahAuditLog as ShariahAudit;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AgmSessionResource extends Resource
{
    protected static ?string $model = AgmSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'AGM & Voting';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'open' => 'Open',
                        'closed' => 'Closed',
                    ])->native(false)->required()->default('draft'),
                Forms\Components\Select::make('voting_type')
                    ->options([
                        'one_member_one_vote' => 'One Member, One Vote',
                        'share_percentage' => 'Share Percentage (Weighted)',
                    ])->native(false)->required()->default('one_member_one_vote'),
                Forms\Components\TextInput::make('minimum_quorum')
                    ->numeric()
                    ->helperText('Minimum number of voters required for valid results (optional)'),
                Forms\Components\DateTimePicker::make('start_at')->seconds(false)->native(false),
                Forms\Components\DateTimePicker::make('end_at')->seconds(false)->native(false),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'asc')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('status')->badge()
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'open',
                        'gray' => 'closed',
                    ])->sortable(),
                TextColumn::make('voting_type')->badge()->sortable(),
                TextColumn::make('start_at')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('end_at')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('created_at')->since()->label('Created')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'open' => 'Open',
                    'closed' => 'Closed',
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('open_session')
                    ->label('Open Session')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'open', 'start_at' => now()]);
                        ShariahAudit::log(auth()->user(), 'open_agm_session', [
                            'session_id' => $record->id,
                            'name' => $record->name,
                        ]);
                    }),
                Tables\Actions\Action::make('close_session')
                    ->label('Close Session')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'open')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'closed', 'end_at' => now()]);
                        ShariahAudit::log(auth()->user(), 'close_agm_session', [
                            'session_id' => $record->id,
                            'name' => $record->name,
                        ]);
                    }),
                Tables\Actions\Action::make('export_results')
                    ->label('Export CSV')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->action(function ($record) {
                        return response()->streamDownload(function () use ($record) {
                            $handle = fopen('php://output', 'w');
                            fputcsv($handle, ['Position', 'Candidate', 'Weight (Votes)']);

                            $votes = \App\Models\AgmVote::query()
                                ->select('position', 'candidate_id', DB::raw('SUM(weight) as total_weight'))
                                ->where('session_id', $record->id)
                                ->groupBy('position', 'candidate_id')
                                ->get();

                            $candidates = \App\Models\AgmCandidate::query()->where('session_id', $record->id)->get()->keyBy('id');

                            foreach ($votes as $v) {
                                fputcsv($handle, [
                                    $v->position,
                                    optional($candidates->get($v->candidate_id))->name ?? 'Unknown',
                                    $v->total_weight
                                ]);
                            }
                            fclose($handle);
                        }, "agm_results_{$record->id}.csv");
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            CandidatesRelationManager::class,
            VotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgmSessions::route('/'),
            'create' => Pages\CreateAgmSession::route('/create'),
            'edit' => Pages\EditAgmSession::route('/{record}/edit'),
        ];
    }
    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_agm_session');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_agm_session');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_agm_session');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_records'); // Or delete_agm_session
    }
}
