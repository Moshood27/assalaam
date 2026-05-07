<?php

namespace App\Filament\Resources\MemberApplicationResource\Pages;

use App\Filament\Resources\MemberApplicationResource;
use App\Models\MemberApplication;
use App\Models\ShariahAuditLog as ShariahAudit;
use App\Models\User;
use App\Mail\NewMemberWelcome;
use App\Mail\MemberApplicationRejected;
use App\Mail\MemberApplicationInterviewInvitation;
use App\Services\SmsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ViewMemberApplication extends ViewRecord
{
    protected static string $resource = MemberApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label('Download Form')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->action(fn (MemberApplication $record) => response()->streamDownload(function () use ($record) {
                    echo Pdf::loadView('pdfs.membership_application', ['application' => $record])->output();
                }, "membership-application-{$record->id}.pdf")),
            Actions\Action::make('download_imam')
                ->label('Download Imam Attestation')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->action(fn (MemberApplication $record) => response()->streamDownload(function () use ($record) {
                    echo Pdf::loadView('pdfs.imam_attestation', ['application' => $record])->output();
                }, "imam-attestation-{$record->id}.pdf")),
            Actions\Action::make('invite_to_meeting')
                ->label('Invite to Meeting')
                ->icon('heroicon-o-calendar-days')
                ->color('warning')
                ->visible(fn (MemberApplication $record) => $record->finalized_at === null && $record->submitted_at !== null)
                ->form([
                    Forms\Components\Select::make('meeting_type')
                        ->options([
                            'online' => 'Online Meeting',
                            'physical' => 'Physical Meeting',
                        ])
                        ->required()
                        ->live(),
                    Forms\Components\DateTimePicker::make('meeting_date_time')
                        ->label('Meeting Date & Time')
                        ->required(),
                    Forms\Components\TextInput::make('location_or_link')
                        ->label(fn (Forms\Get $get) => $get('meeting_type') === 'online' ? 'Meeting Link' : 'Location')
                        ->placeholder(fn (Forms\Get $get) => $get('meeting_type') === 'online' ? 'https://zoom.us/j/...' : 'Main Branch Office')
                        ->required(),
                    Forms\Components\Textarea::make('custom_message')
                        ->label('Additional Message (Optional)')
                        ->rows(3),
                ])
                ->action(function (MemberApplication $record, array $data) {
                    // Send Email
                    try {
                        Mail::to($record->email)->send(new MemberApplicationInterviewInvitation(
                            name: $record->name,
                            meetingType: $data['meeting_type'],
                            meetingDateTime: \Carbon\Carbon::parse($data['meeting_date_time'])->format('M d, Y h:i A'),
                            meetingLocationOrLink: $data['location_or_link'],
                            customMessage: $data['custom_message'] ?? null
                        ));
                    } catch (\Exception $e) {
                        Log::error('Failed to send interview invitation email', ['error' => $e->getMessage()]);
                    }

                    // Send SMS
                    $smsService = app(SmsService::class);
                    if ($record->phone) {
                        $appName = config('app.name');
                        $typeStr = ucfirst($data['meeting_type']);
                        $dateTime = \Carbon\Carbon::parse($data['meeting_date_time'])->format('M d, h:i A');
                        $smsMessage = "Salam {$record->name}, you are invited to a {$typeStr} interview for {$appName} on {$dateTime}. Detail: {$data['location_or_link']}";

                        try {
                            $smsService->send($record->phone, $smsMessage);
                        } catch (\Exception $e) {
                            Log::error('Failed to send interview invitation SMS', ['error' => $e->getMessage()]);
                        }
                    }

                    Notification::make()
                        ->title('Invitation Sent')
                        ->body('The applicant has been invited via email and SMS.')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (MemberApplication $record) => $record->finalized_at === null && $record->submitted_at !== null)
                ->requiresConfirmation()
                ->action(function (MemberApplication $record) {
                    $user = DB::transaction(function () use ($record) {
                        $now = now();
                        $admissionDate = $record->admission_date ?? $now;
                        $admissionOfficer = $record->admission_officer_name ?? auth()->user()->full_name;

                        $password = null;
                        if ($record->password_hash) {
                            try {
                                $password = \Illuminate\Support\Facades\Crypt::decryptString($record->password_hash);
                            } catch (\Throwable $e) {
                                $password = \Illuminate\Support\Str::random(12);
                            }
                        }

                        $membership = User::generateMembershipNumber((int) $record->branch_id);

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
            Actions\Action::make('reject')
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
        ];
    }
}
