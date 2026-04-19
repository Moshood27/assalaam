<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberApplicationResource\Pages;
use App\Models\MemberApplication;
use App\Models\ShariahAuditLog as ShariahAudit;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
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
                Forms\Components\Tabs::make('Application Details')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Personal & Contact')
                            ->schema([
                                Forms\Components\Section::make('Basic Personal Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')->required(),
                                        Forms\Components\TextInput::make('surname')->required(),
                                        Forms\Components\TextInput::make('other_names')->required(),
                                        Forms\Components\Select::make('gender')
                                            ->options([
                                                'male' => 'Male',
                                                'female' => 'Female',
                                            ]),
                                        Forms\Components\TextInput::make('native_place')->label('Native (State or Town of Origin)'),
                                        Forms\Components\DatePicker::make('dob')->label('Date of Birth'),
                                        Forms\Components\Select::make('marital_status')
                                            ->options([
                                                'single' => 'Single',
                                                'married' => 'Married',
                                                'divorced' => 'Divorced',
                                                'widow' => 'Widow',
                                            ]),
                                        Forms\Components\TextInput::make('occupation'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Contact Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('email')->email()->required(),
                                        Forms\Components\TextInput::make('phone')->required(),
                                        Forms\Components\TextInput::make('secondary_phone'),
                                        Forms\Components\Textarea::make('residential_address')->rows(2),
                                        Forms\Components\Textarea::make('permanent_address')->rows(2),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Business & Kin')
                            ->schema([
                                Forms\Components\Section::make('Business & Professional Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('nature_of_business'),
                                        Forms\Components\Textarea::make('business_address')->rows(2),
                                        Forms\Components\Toggle::make('has_other_cooperatives')
                                            ->label('Other Cooperative Affiliations'),
                                        Forms\Components\Textarea::make('other_cooperative_details')
                                            ->visible(fn (callable $get) => $get('has_other_cooperatives'))
                                            ->rows(2),
                                    ])->columns(2),

                                Forms\Components\Section::make('Next of Kin')
                                    ->schema([
                                        Forms\Components\TextInput::make('nok_name')->label('Next of Kin Name'),
                                        Forms\Components\TextInput::make('nok_phone')->label('Next of Kin Phone'),
                                        Forms\Components\TextInput::make('nok_relationship')->label('Relationship'),
                                        Forms\Components\Textarea::make('nok_address')->label('Next of Kin Address')->rows(2),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Guarantor & Religious')
                            ->schema([
                                Forms\Components\Section::make('Guarantor Details')
                                    ->schema([
                                        Forms\Components\TextInput::make('guarantor_name'),
                                        Forms\Components\TextInput::make('guarantor_phone'),
                                        Forms\Components\TextInput::make('guarantor_occupation'),
                                        Forms\Components\Textarea::make('guarantor_address')->rows(2),
                                        Forms\Components\FileUpload::make('guarantor_signature_path')
                                            ->label('Guarantor Signature')
                                            ->image(),
                                    ])->columns(2),

                                Forms\Components\Section::make('Religious Information & Imam\'s Attestation')
                                    ->schema([
                                        Forms\Components\TextInput::make('religious_society_name'),
                                        Forms\Components\TextInput::make('imam_name')->label('Imam/Amir Name'),
                                        Forms\Components\TextInput::make('imam_phone')->label('Imam/Amir Phone'),
                                        Forms\Components\TextInput::make('duration_of_jamma_membership'),
                                        Forms\Components\Textarea::make('mosque_address')->rows(2),
                                        Forms\Components\Toggle::make('imam_approval_status')->label('Imam\'s Approval Status'),
                                        Forms\Components\DateTimePicker::make('imam_approved_at'),
                                        Forms\Components\FileUpload::make('imam_signature_path')
                                            ->label('Imam Signature')
                                            ->image(),
                                    ])->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make('Female Members & Documents')
                            ->schema([
                                Forms\Components\Section::make('Information for Female Members (Wali/Spouse Details)')
                                    ->schema([
                                        Forms\Components\TextInput::make('spouse_father_name')->label('Father/Spouse Name'),
                                        Forms\Components\TextInput::make('spouse_father_phone')->label('Father/Spouse Phone'),
                                        Forms\Components\Textarea::make('spouse_father_address')->label('Residential Address')->rows(2),
                                        Forms\Components\Textarea::make('spouse_father_business_address')->label('Business Address')->rows(2),
                                        Forms\Components\FileUpload::make('spouse_father_consent_signature_path')
                                            ->label('Consent Signature')
                                            ->image(),
                                    ])->columns(2),

                                Forms\Components\Section::make('Documents')
                                    ->schema([
                                        Forms\Components\FileUpload::make('passport_path')->label('Passport')->image(),
                                        Forms\Components\FileUpload::make('id_card_path')->label('ID Card'),
                                        Forms\Components\FileUpload::make('proof_of_address_path')->label('Proof of Address'),
                                    ])->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make('Official Use')
                            ->schema([
                                Forms\Components\Section::make('Official Use Only (Admin Workflow)')
                                    ->schema([
                                        Forms\Components\TextInput::make('admission_form_number'),
                                        Forms\Components\DatePicker::make('admission_date'),
                                        Forms\Components\TextInput::make('admission_officer_name'),
                                        Forms\Components\Select::make('approval_status')
                                            ->options([
                                                'pending' => 'Pending',
                                                'recommended' => 'Recommended',
                                                'approved' => 'Approved',
                                                'rejected' => 'Rejected',
                                            ]),
                                        Forms\Components\Textarea::make('officer_recommendation')->rows(3)->columnSpanFull(),
                                        Forms\Components\FileUpload::make('president_signature_path')->label('President\'s Signature')->image(),
                                        Forms\Components\DateTimePicker::make('president_signed_at'),
                                        Forms\Components\FileUpload::make('secretary_general_signature_path')->label('Secretary General\'s Signature')->image(),
                                        Forms\Components\DateTimePicker::make('secretary_general_signed_at'),
                                    ])->columns(2),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderByRaw('LENGTH(surname) ASC')->orderBy('surname', 'asc');
            })
            ->columns([
                Tables\Columns\ImageColumn::make('passport_path')
                    ->label('Photo')
                    ->circular(),
                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['name', 'surname', 'other_names'])
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderBy("surname", $direction)
                            ->orderBy("name", $direction)
                            ->orderBy("other_names", $direction);
                    }),
                TextColumn::make('email')->searchable(),
                TextColumn::make('phone')->searchable(),
                Tables\Columns\TextColumn::make('approval_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'recommended' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('submitted_at')->dateTime()->sortable(),
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
                Tables\Actions\Action::make('download')
                    ->label('Download Form')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(fn (MemberApplication $record) => response()->streamDownload(function () use ($record) {
                        echo Pdf::loadView('pdfs.membership_application', ['application' => $record])->output();
                    }, "membership-application-{$record->id}.pdf")),
                Tables\Actions\Action::make('download_imam')
                    ->label('Download Imam Attestation')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->action(fn (MemberApplication $record) => response()->streamDownload(function () use ($record) {
                        echo Pdf::loadView('pdfs.imam_attestation', ['application' => $record])->output();
                    }, "imam-attestation-{$record->id}.pdf")),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (MemberApplication $record) => $record->finalized_at === null && $record->submitted_at !== null)
                    ->requiresConfirmation()
                    ->action(function (MemberApplication $record) {
                        $user = DB::transaction(function () use ($record) {
                            $now = now();
                            $admissionDate = $record->admission_date ?? $now;
                            $admissionOfficer = $record->admission_officer_name ?? auth()->user()->name;

                            // Decrypt the stored password
                            $password = null;
                            if ($record->password_hash) {
                                try {
                                    $password = \Illuminate\Support\Facades\Crypt::decryptString($record->password_hash);
                                } catch (\Throwable $e) {
                                    // Fallback if decryption fails (though it shouldn't)
                                    $password = \Illuminate\Support\Str::random(12);
                                }
                            }

                            // Generate a unique membership number within the branch (6 digits)
                            $membership = User::generateMembershipNumber((int) $record->branch_id);

                            // Create the user
                            $user = User::create([
                                'name' => $record->name,
                                'surname' => $record->surname,
                                'other_names' => $record->other_names,
                                'gender' => $record->gender,
                                'native_place' => $record->native_place,
                                'dob' => $record->dob,
                                'marital_status' => $record->marital_status,
                                'occupation' => $record->occupation,
                                'email' => $record->email,
                                'phone' => $record->phone,
                                'secondary_phone' => $record->secondary_phone,
                                'address' => $record->address,
                                'residential_address' => $record->residential_address,
                                'permanent_address' => $record->permanent_address,
                                'branch_id' => $record->branch_id,
                                'nature_of_business' => $record->nature_of_business,
                                'business_address' => $record->business_address,
                                'has_other_cooperatives' => $record->has_other_cooperatives,
                                'other_cooperative_details' => $record->other_cooperative_details,
                                'nok_name' => $record->nok_name,
                                'nok_address' => $record->nok_address,
                                'nok_phone' => $record->nok_phone,
                                'nok_relationship' => $record->nok_relationship,
                                'guarantor_name' => $record->guarantor_name,
                                'guarantor_address' => $record->guarantor_address,
                                'guarantor_phone' => $record->guarantor_phone,
                                'guarantor_occupation' => $record->guarantor_occupation,
                                'guarantor_signature_path' => $record->guarantor_signature_path,
                                'religious_society_name' => $record->religious_society_name,
                                'imam_name' => $record->imam_name,
                                'mosque_address' => $record->mosque_address,
                                'imam_phone' => $record->imam_phone,
                                'duration_of_jamma_membership' => $record->duration_of_jamma_membership,
                                'imam_approval_status' => $record->imam_approval_status,
                                'imam_approved_at' => $record->imam_approved_at,
                                'imam_signature_path' => $record->imam_signature_path,
                                'spouse_father_name' => $record->spouse_father_name,
                                'spouse_father_address' => $record->spouse_father_address,
                                'spouse_father_business_address' => $record->spouse_father_business_address,
                                'spouse_father_phone' => $record->spouse_father_phone,
                                'spouse_father_consent_signature_path' => $record->spouse_father_consent_signature_path,
                                'admission_form_number' => $record->admission_form_number,
                                'admission_date' => $admissionDate,
                                'admission_officer_name' => $admissionOfficer,
                                'officer_recommendation' => $record->officer_recommendation,
                                'approval_status' => 'approved',
                                'president_signature_path' => $record->president_signature_path,
                                'president_signed_at' => $record->president_signed_at,
                                'secretary_general_signature_path' => $record->secretary_general_signature_path,
                                'secretary_general_signed_at' => $record->secretary_general_signed_at,
                                'membership_number' => $membership,
                                'password' => $password,
                                'email_verified_at' => $record->email_verified_at,
                                'passport_path' => $record->passport_path,
                                'id_card_path' => $record->id_card_path,
                                'proof_of_address_path' => $record->proof_of_address_path,
                                'balance' => 0,
                            ]);

                            $record->approval_status = 'approved';
                            $record->admission_date = $admissionDate;
                            $record->admission_officer_name = $admissionOfficer;
                            $record->finalized_at = $now;
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
                        $record->approval_status = 'rejected';
                        $record->officer_recommendation = $data['reason'];
                        $record->admission_officer_name = auth()->user()->name;
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
