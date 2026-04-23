<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDistanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_mark_attendance_if_too_far()
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $branch->id,
            'membership_number' => 'MEM001',
        ]);

        $meeting = Meeting::create([
            'name' => 'Monthly Meeting',
            'date' => now()->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'venue_lat' => 6.5244,
            'venue_lng' => 3.3792,
            'radius_meters' => 50,
            'pin' => '1234',
            'status' => 'ongoing',
        ]);

        // Latitude 6.5254 is roughly 110 meters away from 6.5244 (approx 111m per 0.001 degree)
        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/mark-attendance", [
            'pin' => '1234',
            'lat' => 6.5254,
            'lng' => 3.3792,
            'device_uuid' => 'device-123',
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'message' => 'You are too far from the venue. You must be within 50 meters.'
        ]);
    }

    public function test_user_can_mark_attendance_if_within_radius()
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $branch->id,
            'membership_number' => 'MEM001',
        ]);

        $meeting = Meeting::create([
            'name' => 'Monthly Meeting',
            'date' => now()->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'venue_lat' => 6.5244,
            'venue_lng' => 3.3792,
            'radius_meters' => 50,
            'pin' => '1234',
            'status' => 'ongoing',
        ]);

        // 6.52441 is very close, well within 50m
        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/mark-attendance", [
            'pin' => '1234',
            'lat' => 6.52441,
            'lng' => 3.3792,
            'device_uuid' => 'device-123',
        ]);

        $response->assertStatus(200);
    }
}
