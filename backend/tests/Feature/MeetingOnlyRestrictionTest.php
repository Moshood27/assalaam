<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingOnlyRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_device_cannot_be_used_by_multiple_users_in_same_meeting()
    {
        // Setup
        $branch = Branch::create([
            'name' => 'Test Branch',
            'latitude' => 6.5244,
            'longitude' => 3.3792,
        ]);

        $user1 = User::create([
            'name' => 'User One',
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $branch->id,
            'membership_number' => 'MEM001',
        ]);

        $user2 = User::create([
            'name' => 'User Two',
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $branch->id,
            'membership_number' => 'MEM002',
        ]);

        $meeting = Meeting::create([
            'name' => 'Monthly Meeting',
            'date' => now()->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'venue_lat' => 6.5244,
            'venue_lng' => 3.3792,
            'radius_meters' => 1000,
            'pin' => '1234',
            'status' => 'ongoing',
        ]);

        $deviceUuid = 'device-12345';

        // User 1 marks attendance
        $response1 = $this->actingAs($user1)->postJson("/api/meetings/{$meeting->id}/mark-attendance", [
            'pin' => '1234',
            'lat' => 6.5244,
            'lng' => 3.3792,
            'device_uuid' => $deviceUuid,
        ]);

        $response1->assertStatus(200);
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user1->id,
            'meeting_id' => $meeting->id,
            'device_uuid' => $deviceUuid,
        ]);

        // User 2 tries to mark attendance with SAME device
        $response2 = $this->actingAs($user2)->postJson("/api/meetings/{$meeting->id}/mark-attendance", [
            'pin' => '1234',
            'lat' => 6.5244,
            'lng' => 3.3792,
            'device_uuid' => $deviceUuid,
        ]);

        $response2->assertStatus(403);
        $response2->assertJsonFragment([
            'message' => 'This device has already been used to mark attendance for another member in this meeting.'
        ]);

        // User 2 marks attendance with DIFFERENT device
        $differentDeviceUuid = 'device-67890';
        $response3 = $this->actingAs($user2)->postJson("/api/meetings/{$meeting->id}/mark-attendance", [
            'pin' => '1234',
            'lat' => 6.5244,
            'lng' => 3.3792,
            'device_uuid' => $differentDeviceUuid,
        ]);

        $response3->assertStatus(200);
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user2->id,
            'meeting_id' => $meeting->id,
            'device_uuid' => $differentDeviceUuid,
        ]);
    }
}
