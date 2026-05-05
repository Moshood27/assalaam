<?php

namespace App\Filament\Resources\MemberApplicationResource\Pages;

use App\Filament\Traits\HasWipeAction;
use App\Filament\Resources\MemberApplicationResource;
use App\Mail\MemberInvitation;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ListMemberApplications extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = MemberApplicationResource::class;

    public function getSubheading(): ?string
    {
        return 'Review and process new membership applications and registrations.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Action::make('invite')
                ->label('Invite Member')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->form([
                    TextInput::make('name')
                        ->required()
                        ->label('Applicant Name'),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->label('Email Address'),
                    Textarea::make('message')
                        ->label('Custom Message (Optional)')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        Mail::to($data['email'])->send(new MemberInvitation($data['name'], $data['message']));
                        Notification::make()
                            ->title('Invitation Sent')
                            ->body("An invitation has been sent to {$data['email']}.")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Log::error('Failed to send invitation email', ['error' => $e->getMessage()]);
                        Notification::make()
                            ->title('Error')
                            ->body('Failed to send invitation email.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
