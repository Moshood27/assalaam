<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectProposalResource\Pages;
use App\Filament\Resources\ProjectProposalResource\RelationManagers\CommentsRelationManager;
use App\Models\ProjectProposal;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectProposalResource extends Resource
{
    protected static ?string $model = ProjectProposal::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?string $navigationGroup = 'AGM & Voting';
    protected static ?int $navigationSort = 35;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Proposer')
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['surname', 'name', 'other_names'])
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('target_amount')
                    ->numeric()
                    ->prefix('₦'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'voting' => 'Voting',
                        'closed' => 'Closed',
                        'rejected' => 'Rejected',
                    ])->native(false)->required()->default('pending'),
                Forms\Components\Select::make('voting_type')
                    ->options([
                        'one_member_one_vote' => 'One Member, One Vote',
                        'share_percentage' => 'Share Percentage (Weighted)',
                    ])->native(false)->required()->default('one_member_one_vote'),
                Forms\Components\TextInput::make('minimum_quorum')
                    ->numeric()
                    ->helperText('Minimum number of voters required for valid results (optional)'),
                Forms\Components\Section::make('Sharia Review')
                    ->schema([
                        Forms\Components\Select::make('sharia_status')
                            ->options([
                                'pending_review' => 'Pending Review',
                                'compliant' => 'Compliant',
                                'non_compliant' => 'Non-Compliant',
                            ])->native(false)->required()->default('pending_review'),
                        Forms\Components\FileUpload::make('sharia_certificate_path')
                            ->label('Sharia Certificate / Fatwa (PDF)')
                            ->directory('sharia-certificates')
                            ->acceptedFileTypes(['application/pdf']),
                        Forms\Components\Textarea::make('fatwa_summary')
                            ->label('Fatwa Summary')
                            ->placeholder('Brief summary of the Sharia Board decision')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('sharia_notes')
                            ->label('Internal Sharia Notes')
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\DateTimePicker::make('voting_start_at')->seconds(false)->native(false),
                Forms\Components\DateTimePicker::make('voting_end_at')->seconds(false)->native(false),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.full_name')
                    ->label('Proposer')
                    ->searchable(['surname', 'name', 'other_names'])
                    ->sortable(),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('status')->badge()
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'approved',
                        'success' => 'voting',
                        'gray' => 'closed',
                        'danger' => 'rejected',
                    ])->sortable(),
                TextColumn::make('voting_type')->badge()->sortable(),
                TextColumn::make('sharia_status')->badge()
                    ->colors([
                        'warning' => 'pending_review',
                        'success' => 'compliant',
                        'danger' => 'non_compliant',
                    ])->sortable(),
                TextColumn::make('target_amount')->money('NGN')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'voting' => 'Voting',
                    'closed' => 'Closed',
                    'rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('promote_to_project')
                    ->label('Promote to Project')
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => \App\Models\Project::where('name', $record->title)->exists())
                    ->action(function ($record) {
                        \App\Models\Project::create([
                            'name' => $record->title,
                            'description' => $record->description,
                            'target_amount' => $record->target_amount,
                            'active' => true,
                            'started_at' => now(),
                        ]);

                        $record->update(['status' => 'closed']);

                        \Filament\Notifications\Notification::make()
                            ->title('Promoted to Project')
                            ->success()
                            ->body("The proposal '{$record->title}' has been successfully promoted to an active project.")
                            ->send();
                    }),
                Tables\Actions\Action::make('promote_to_sadaqah')
                    ->label('Promote to Sadaqah')
                    ->icon('heroicon-o-heart')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => \App\Models\SadaqahProject::where('name', $record->title)->exists())
                    ->action(function ($record) {
                        \App\Models\SadaqahProject::create([
                            'name' => $record->title,
                            'description' => $record->description,
                            'target_amount' => $record->target_amount,
                            'type' => 'general',
                            'active' => true,
                            'started_at' => now(),
                        ]);

                        $record->update(['status' => 'closed']);

                        \Filament\Notifications\Notification::make()
                            ->title('Promoted to Sadaqah Project')
                            ->success()
                            ->body("The proposal '{$record->title}' has been successfully promoted to an active Sadaqah Jariyah project.")
                            ->send();
                    }),
                Tables\Actions\Action::make('export_results')
                    ->label('Export CSV')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->action(function ($record) {
                        return response()->streamDownload(function () use ($record) {
                            $handle = fopen('php://output', 'w');
                            fputcsv($handle, ['Choice', 'Weight (Votes)']);

                            $votes = \App\Models\ProjectProposalVote::query()
                                ->select('choice', DB::raw('SUM(weight) as total_weight'))
                                ->where('project_proposal_id', $record->id)
                                ->groupBy('choice')
                                ->get();

                            foreach ($votes as $v) {
                                fputcsv($handle, [
                                    strtoupper($v->choice),
                                    $v->total_weight
                                ]);
                            }
                            fclose($handle);
                        }, "proposal_results_{$record->id}.csv");
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
            CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectProposals::route('/'),
            'create' => Pages\CreateProjectProposal::route('/create'),
            'edit' => Pages\EditProjectProposal::route('/{record}/edit'),
        ];
    }
}
