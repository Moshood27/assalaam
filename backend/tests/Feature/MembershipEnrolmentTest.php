<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipEnrolmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_download_membership_enrolment()
    {
        $user = User::factory()->create([
            'membership_number' => 'TEST-001',
            'surname' => 'Doe',
            'other_names' => 'John',
            'gender' => 'male',
            'dob' => '1990-01-01',
        ]);

        $response = $this->actingAs($user)->get('/api/download-membership-enrolment');

        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
