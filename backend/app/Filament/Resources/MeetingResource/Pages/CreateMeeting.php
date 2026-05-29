<?php

namespace App\Filament\Resources\MeetingResource\Pages;

use App\Filament\Resources\MeetingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMeeting extends CreateRecord
{
    protected static string $resource = MeetingResource::class;

    protected function afterCreate(): void
    {
        $meeting = $this->record;

        if ($meeting->status === 'scheduled') {
            $date = $meeting->date->format('l, jS F Y');
            $time = date('h:i A', strtotime($meeting->start_time));

            $meeting->notifyMembers(
                "�“… New Meeting Scheduled: {$meeting->name}",
                "A new meeting has been scheduled for {$date} at {$time}. Please mark your calendar.",
                ['type' => 'meeting_scheduled']
            );
        }
    }
}
