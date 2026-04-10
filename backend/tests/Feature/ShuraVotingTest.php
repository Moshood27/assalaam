<?php

namespace Tests\Feature;

use App\Models\AgmCandidate;
use App\Models\AgmSession;
use App\Models\Contribution;
use App\Models\ProjectProposal;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShuraVotingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $sharesScheme;

    protected function setUp(): void
    {
        parent::setUp();
        // Create necessary schemes
        Scheme::create(['name' => 'Savings', 'active' => true]);
        $this->sharesScheme = Scheme::create(['name' => 'Shares', 'active' => true]);

        $this->user = User::factory()->create();
    }

    public function test_weighted_voting_in_agm()
    {
        // 1. Give user 500.50 shares
        Contribution::create([
            'user_id' => $this->user->id,
            'scheme_id' => $this->sharesScheme->id,
            'amount' => 500.50,
            'status' => 'success',
            'reference' => 'test_shares_' . uniqid()
        ]);

        // 2. Create weighted session
        $session = AgmSession::create([
            'name' => 'Weighted AGM 2026',
            'status' => 'open',
            'voting_type' => 'share_percentage'
        ]);

        $candidate = AgmCandidate::create([
            'session_id' => $session->id,
            'name' => 'John Doe',
            'position' => 'President'
        ]);

        // 3. Vote
        $response = $this->actingAs($this->user)
            ->postJson("/api/agm/sessions/{$session->id}/vote", [
                'candidate_id' => $candidate->id
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('agm_votes', [
            'user_id' => $this->user->id,
            'weight' => 500.50
        ]);

        // 4. Check results
        $response = $this->actingAs($this->user)
            ->getJson("/api/agm/sessions/{$session->id}/results");

        $response->assertStatus(200);
        $response->assertJsonPath('results.President.0.votes', 500.50);
    }

    public function test_project_proposal_submission_and_voting()
    {
        // 1. Submit proposal
        $response = $this->actingAs($this->user)
            ->postJson('/api/project-proposals', [
                'title' => 'Fish Farm',
                'description' => 'Should we start a fish farm?',
                'target_amount' => 1000000
            ]);

        $response->assertStatus(201);
        $proposalId = $response->json('proposal.id');

        // 2. Admin approves and opens voting
        $proposal = ProjectProposal::find($proposalId);
        $proposal->update([
            'status' => 'voting',
            'voting_type' => 'one_member_one_vote'
        ]);

        // 3. Vote YES
        $response = $this->actingAs($this->user)
            ->postJson("/api/project-proposals/{$proposalId}/vote", [
                'choice' => 'yes'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('project_proposal_votes', [
            'project_proposal_id' => $proposalId,
            'user_id' => $this->user->id,
            'choice' => 'yes',
            'weight' => 1.00
        ]);

        // 4. Check results
        $response = $this->actingAs($this->user)
            ->getJson("/api/project-proposals/{$proposalId}");

        $response->assertStatus(200);
        $response->assertJsonPath('results.yes', 1.00);
        $response->assertJsonPath('results.no', 0.00);
        $response->assertJsonPath('my_vote', 'yes');
    }

    public function test_project_proposal_weighted_voting()
    {
        // 1. Give user 750 shares
        Contribution::create([
            'user_id' => $this->user->id,
            'scheme_id' => $this->sharesScheme->id,
            'amount' => 750.00,
            'status' => 'success',
            'reference' => 'test_shares_' . uniqid()
        ]);

        // 2. Create proposal with weighted voting
        $proposal = ProjectProposal::create([
            'user_id' => $this->user->id,
            'title' => 'Poultry Farm',
            'description' => 'Weighted vote test',
            'status' => 'voting',
            'voting_type' => 'share_percentage'
        ]);

        // 3. Vote NO
        $response = $this->actingAs($this->user)
            ->postJson("/api/project-proposals/{$proposal->id}/vote", [
                'choice' => 'no'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('project_proposal_votes', [
            'project_proposal_id' => $proposal->id,
            'user_id' => $this->user->id,
            'choice' => 'no',
            'weight' => 750.00
        ]);

        // 4. Check results
        $response = $this->actingAs($this->user)
            ->getJson("/api/project-proposals/{$proposal->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('results.no', 750.00);
        $response->assertJsonPath('results.yes', 0.00);
    }
}
