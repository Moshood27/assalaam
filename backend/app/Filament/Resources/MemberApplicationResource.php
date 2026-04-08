<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberApplicationResource\Pages;
use App\Models\MemberApplication;
use App\Models\ShariahAuditLog as ShariahAudit;
use App\Models\User;
use App\Mail\NewMemberWelcome;
use App\Mail\MemberApplicationRejected;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MemberApplicationResource extends Resource
{
    protected static ?string $model = MemberApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationGroup = 'Member Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Applicant Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')->disabled(),
                        Forms\Components\TextInput::make('email')->disabled(),
                        Forms\Components\TextInput::make('phone')->disabled(),
                        Forms\Components\TextInput::make('address')->disabled(),
                        Forms\Components\Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->disabled(),
                    ])->columns(2),
                Forms\Components\Section::make('Verification Status')
                    ->schema([
                        Forms\Components\DateTimePicker::make('email_verified_at')->disabled(),
                        Forms\Components\DateTimePicker::make('phone_verified_at')->disabled(),
                        Forms\Components\DateTimePicker::make('submitted_at')->disabled(),
                        Forms\Components\DateTimePicker::make('finalized_at')->disabled(),
                    ])->columns(2),
                Forms\Components\Section::make('Documents')
                    ->schema([
                        Forms\Components\FileUpload::make('passport_path')->label('Passport')->disabled(),
                        Forms\Components\FileUpload::make('id_card_path')->label('ID Card')->disabled(),
                        Forms\Components\FileUpload::make('proof_of_address_path')->label('Proof of Address')->disabled(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('submitted_at')->dateTime()->sortable(),
                TextColumn::make('finalized_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('submitted')
                    ->query(fn ($query) => $query->whereNotNull('submitted_at')),
                Tables\Filters\Filter::make('pending')
                    ->query(fn ($query) => $query->whereNull('finalized_at')),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->hasRole('super_admin')), // Only visible to Super Admin
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (MemberApplication $record) => $record->finalized_at === null && $record->submitted_at !== null)
                    ->requiresConfirmation()
                    ->action(function (MemberApplication $record) {
                        $user = DB::transaction(function () use ($record) {
                            // Generate a unique membership number within the branch (6 digits)
                            $membership = User::generateMembershipNumber((int) $record->branch_id);

                            // Create the user
                            $user = User::create([
                                'name' => $record->name,
                                'email' => $record->email,
                                'phone' => $record->phone,
                                'address' => $record->address,
                                'branch_id' => $record->branch_id,
                                'membership_number' => $membership,
                                'password' => $record->password_hash, // Already hashed during app submission
                                'email_verified_at' => $record->email_verified_at,
                                'passport_path' => $record->passport_path,
                                'balance' => 0,
                            ]);

                            $record->finalized_at = now();
                            $record->save();

                            ShariahAudit::log(auth()->user(), 'approve_member_application', [
                                'application_id' => $record->id,
                                'user_id' => $user->id,
                                'email' => $user->email,
                            ]);

                            return $user;
                        });

                        // Send welcome email
                        try {
                            Mail::to($user->email)->send(new NewMemberWelcome($user));
                        } catch (\Exception $e) {
                            Log::error('Failed to send welcome email', ['error' => $e->getMessage()]);
                        }

                        Notification::make()
                            ->title('Application Approved')
                            ->body('A new member account has been created and a welcome email has been sent.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (MemberApplication $record) => $record->finalized_at === null && $record->submitted_at !== null)
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for rejection')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->requiresConfirmation()
                    ->action(function (MemberApplication $record, array $data) {
                        $record->finalized_at = now();
                        $record->save();

                        ShariahAudit::log(auth()->user(), 'reject_member_application', [
                            'application_id' => $record->id,
                            'reason' => $data['reason'],
                        ]);

                        // Send rejection email
                        try {
                            Mail::to($record->email)->send(new MemberApplicationRejected($record, $data['reason']));
                        } catch (\Exception $e) {
                            Log::error('Failed to send rejection email', ['error' => $e->getMessage()]);
                        }

                        Notification::make()
                            ->title('Application Rejected')
                            ->body('The application has been rejected and the applicant has been notified.')
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('super_admin')), // Only visible to Super Admin
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMemberApplications::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_member_application');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_member_application');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_member_application');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_member_application');
    }

    public static function getEloquentQuery(): Builder
    {
        $authUser = auth()->user();

        // If not authenticated or unexpected guard result, return no records for safety
        if (!($authUser instanceof \App\Models\User)) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        // If the user is a Super Admin or platform admin, let them see everything
        if ($authUser->hasRole('super_admin') || ($authUser->is_admin === true)) {
            return parent::getEloquentQuery();
        }

        // Otherwise, only show records belonging to the user's branch
        return parent::getEloquentQuery()->where('branch_id', $authUser->branch_id);
    }
}
