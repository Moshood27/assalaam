# Digital AGM & Member Voting

This document explains how the cooperativeâ€™s Annual General Meeting (AGM) and inâ€‘app Member Voting work across the system: data model, APIs, frontend UX, and the Filament admin panel.

Last updated: 2026â€‘03â€‘21


## Goals
- Enable democratic elections of committee/board positions digitally.
- Weighted Voting: Support both "One Member, One Vote" and "Weighted Voting" (based on share percentage).
- Project Proposals: Allow members to submit ideas for new investments.
- Eligibility Gating: Ensure only members in good standing (not defaulters, not deceased) can participate in Shura.
- Sharia Review: Formal workflow for Sharia compliance review of project proposals.
- Sharia Board: Dedicated directory of Sharia scholars and advisors within the app.
- Quorum & Participation: Tracking of eligible voters vs actual participation to ensure constitutional validity of Shura decisions.
- Results Export: Administrators can export voting results to CSV for official record-keeping.
- Project Promotion: Administrators can promote successful project proposals to official investment projects with one click.
- Consultation (Shura): Commenting system for project proposals to allow member discussion.
- Shura Analytics: Centralized dashboard for monitoring participation rates, vote weights, and governance trends across all activities.
- Automated Notifications: Push notifications for voting window openings, status changes, and result publications.
- Automated Scheduling: Console commands (`shura:notify-proposal-voting-open`, `shura:notify-results-published`) to handle high-volume notification delivery.
- Admin Panel: Give admins a simple panel to configure AGM sessions, manage candidates, review project proposals (Sharia & Status), and monitor votes.


## Roles at a Glance
- Member (mobile/web app):
  - Browse active AGM sessions (/agm)
  - View positions and candidates for a session (/agm/sessions/:id)
  - Cast exactly one vote per position per session
  - View live results (vote tallies) per position
- Admin (Filament panel):
  - Create/update AGM sessions (name, status, optional start/end window)
  - Add/manage candidates per session and position
  - View votes (readâ€‘only) and vote counts per candidate


## Data Model
The feature uses three tables/models with constraints that enforce oneâ€‘voteâ€‘perâ€‘position per member:

- AgmSession
  - Fields: id, name, status (draft|open|closed), voting_type (one_member_one_vote|share_percentage), minimum_quorum, start_at, end_at, voting_open_notified_at, results_notified_at, timestamps
  - Computed attributes: is_open (true if status=open OR current time is within [start_at, end_at]), is_within_window
  - Relations: candidates(), votes()
- AgmCandidate
  - Fields: id, session_id (FK), name, position, manifesto, photo_url, timestamps
  - Relations: session(), votes()
- AgmVote
  - Fields: id, session_id (FK), position, user_id (FK), candidate_id (FK), weight, timestamps
  - Unique constraint: (session_id, position, user_id)
  - Relations: session(), candidate(), user()

- ProjectProposal
  - Fields: id, user_id (FK), title, description, target_amount, status (pending|approved|voting|closed|rejected), sharia_status (pending_review|compliant|non_compliant), sharia_notes, sharia_certificate_path, fatwa_summary, voting_type (one_member_one_vote|share_percentage), minimum_quorum, voting_start_at, voting_end_at, voting_open_notified_at, results_notified_at, timestamps
  - Relations: user(), votes(), comments()
- ProjectProposalVote
  - Fields: id, project_proposal_id (FK), user_id (FK), choice (yes|no), weight, timestamps
  - Unique constraint: (project_proposal_id, user_id)
  - Relations: proposal(), user()
- ProjectProposalComment
  - Fields: id, project_proposal_id (FK), user_id (FK), comment, timestamps
  - Relations: proposal(), user()

- ShariaBoardMember
  - Fields: id, name, title, bio, photo_url, is_active, sort_order, timestamps

These are defined in:
- backend/app/Models/AgmSession.php
- backend/app/Models/AgmCandidate.php
- backend/app/Models/AgmVote.php
- backend/database/migrations/2026_03_21_012000_create_agm_sessions_table.php
- backend/database/migrations/2026_03_21_012100_create_agm_candidates_table.php
- backend/database/migrations/2026_03_21_012200_create_agm_votes_table.php


## Voting Rules, Eligibility & Lifecycle
- Eligibility: Participation in Shura (AGM voting and Project Proposals) is restricted to eligible members.
  - Defaulters (is_defaulter = true) are blocked from voting and submitting proposals.
  - Deceased members are blocked.
  - Logic is centralized in `User::isEligibleForShura()`.
- Sessions can be â€œopenâ€ explicitly (status=open) OR implicitly by a schedule window where now âˆˆ [start_at, end_at].
- Project Proposals:
  - Members can submit proposals (if eligible).
  - Proposals undergo Sharia Review (Sharia Status: pending_review -> compliant/non_compliant).
  - Admins can upload a signed Sharia Certificate (PDF) and provide a Fatwa Summary for compliant proposals.
  - Admins must approve and set status to 'voting' for members to cast votes.
  - Members can comment on proposals for consultation (Shura) before/during voting.
- Quorum: Both AGM sessions and Project Proposals support an optional `minimum_quorum`. Participation metrics (total eligible vs total cast) are displayed in results to verify if the quorum was met.
- Members can cast one vote per position per session. Once recorded, it cannot be changed from the app.
- Server validates that the candidate belongs to the given session.
- Server blocks voting if:
  - Session is closed or out of window â†’ 422 Voting is closed
  - Member already voted for that position â†’ 409 You have already voted for <position>
- Results are live tallies grouped by position and candidate. Positions/candidates with zero votes are still listed with 0.


## Backend APIs (Memberâ€‘facing)
All endpoints are protected with Sanctum and the inactivity middleware. Send the member token with Authorization: Bearer <token>.

### AGM Voting
Controller: backend/app/Http/Controllers/Api/AgmController.php
Routes: backend/routes/api.php

- GET /api/agm/sessions
  - Returns latest active/open sessions (status=open or within the configured start/end window).
  - 200 OK: [ { id, name, status, start_at, end_at, is_open, is_within_window, ... }, ... ]

- GET /api/agm/sessions/{id}/candidates
  - Returns candidates grouped by position for the session, plus the callerâ€™s voted_candidate_id per position.
  - 200 OK: {
      session: { ... },
      positions: [
        {
          position: "President",
          candidates: [ { id, name, position, manifesto, photo_url }, ... ],
          voted_candidate_id: number|null
        },
        ...
      ]
    }
  - 404 if session not found.

- POST /api/agm/sessions/{id}/vote
  - Body: { "candidate_id": <number> }
  - 200 OK: { message: "Vote recorded", vote: { ... } }
  - 409 Conflict: { message: "You have already voted for <position>" }
  - 422 Unprocessable Entity when voting closed.
  - 404 if candidate not in that session.

- GET /api/agm/sessions/{id}/results
  - Returns aggregated votes per position (sums weight) and participation metrics.
  - Includes `is_tied: true` flag if multiple candidates are tied at the top for a position.
  - 200 OK: {
      session: { ..., minimum_quorum },
      results: {
        "President": [ { candidate_id, candidate_name, votes }, ... ],
        "Secretary": [ ... ]
      },
      participation: { total_eligible, total_cast, percentage, minimum_quorum, quorum_met }
    }

### Sharia Board
Controller: backend/app/Http/Controllers/Api/ShariaBoardController.php

- GET /api/sharia-board
  - Returns an active list of Sharia Board members for the directory.
  - 200 OK: [ { id, name, title, bio, photo_url, ... }, ... ]

### Project Proposals
Controller: backend/app/Http/Controllers/Api/ProjectProposalController.php
Routes: backend/routes/api.php

- GET /api/project-proposals
  - Returns a list of project proposals.
- POST /api/project-proposals
  - Body: { "title", "description", "target_amount" (optional) }
  - Members can submit new investment ideas.
- GET /api/project-proposals/{id}
  - Returns proposal details, voting status, results (sums weight), participation metrics, and comments.
  - Includes `is_tie: true` if 'yes' and 'no' votes are equal (and non-zero).
  - 200 OK: { proposal: { ..., fatwa_summary, sharia_certificate_path, comments: [...] }, results: { yes, no }, is_tie, participation: { ... }, my_vote: "yes"|"no"|null, is_voting_open }
- POST /api/project-proposals/{id}/vote
  - Body: { "choice": "yes"|"no" }
  - Casts a vote (one_member_one_vote or share_percentage weight).
- POST /api/project-proposals/{id}/comments
  - Body: { "comment": string }
  - Adds a comment to the proposal for consultation.

Authentication & protections:
- Routes are under: Route::middleware(['auth:sanctum', 'inactivity'])->group(...)
- Typical headers: Authorization: Bearer <member_token>


## Frontend (Member App)
Views are in frontend/src/views and routes in frontend/src/router/index.js.

- /agm (Agm.vue)
  - Lists active sessions with status badges and any configured start/end.
  - Navigates to /agm/sessions/:id (Enter button).

- /agm/sessions/:id (AgmSession.vue)
  - Shows positions with candidate cards (photo, name, manifesto).
  - Prevents voting again if you already voted for a position (shows â€œVotedâ€).
  - Confirms selection before submission (native/overridden alert/confirm pattern).
  - Live results section fetches and displays tallies per position; can refresh.

UI safeguards:
- Button disabled if already voted for that position.
- Errors are surfaced with readable messages from API (409, 422, etc.).


## Admin Panel (Filament)
Admins manage AGM via Filament resources in the backend admin panel.

- AgmSessionResource
  - Create/edit sessions with: name, status (draft|open|closed), voting_type, minimum_quorum, start_at, end_at.
  - Actions:
    - Open Session / Close Session (with Sharia Audit logging)
    - Export CSV (Downloads voting results for record-keeping)
  - Filters and badges indicate status; can bulk delete, etc.
  - Relations:
    - Candidates (CandidatesRelationManager)
      - CRUD for candidates with name, position, manifesto, photo URL
      - Shows live votes_count per candidate
    - Votes (VotesRelationManager)
      - Readâ€‘only list of recorded votes: position, candidate, voter, timestamp

- ProjectProposalResource
  - Manage member-submitted investment ideas.
  - Sharia Review section: Set sharia_status, upload Certificate (PDF), and Fatwa Summary.
  - Configuration: status, voting_type, minimum_quorum, start/end times.
  - Actions:
    - Promote to Project: Creates an active `Project` record from the proposal (Available for Approved/Closed proposals).
    - Export CSV (Downloads voting results for record-keeping)
  - Relations:
    - Comments (Consultation history)

- ShariaBoardMemberResource
  - Maintain the directory of Sharia scholars (name, title, bio, photo).
  - Reorderable list for app display sequence.

Usage guidance:
1) Create a new session (status=draft while you add candidates).
2) Add candidates for each position (ensure position names are consistent, e.g., â€œPresidentâ€, â€œSecretaryâ€).
3) Open the session by either:
   - Setting status to open; or
   - Setting a [start_at, end_at] window that includes the current time.
4) Members can now vote in the app. Monitor votes and results from the panel.
5) Close after the election by setting status=closed or ending the time window.


## Security, Privacy, and Integrity
- Authentication: All member endpoints require a valid Sanctum token.
- Oneâ€‘vote enforcement: Backed by database unique constraint (session_id, position, user_id) plus controller checks.
- Auditability: Admin panel exposes readâ€‘only view of votes. Consider limiting access to authorized election officers.
- Privacy: Although the system stores which user voted for whom (for audit), memberâ€‘facing results are aggregated only. If stronger secrecy is required, a future enhancement could pseudonymize votes while preserving constraints and tallies.


## Error Codes & Messages
- 401 Unauthorized: Missing/invalid token
- 404 Not Found: Session or candidate not found
- 409 Conflict: Member already voted for the position in this session
- 422 Unprocessable Entity: Voting closed (status not open and outside [start_at, end_at])


## Example cURL
Replace $TOKEN with a valid member token and $ID with a session ID.

List active sessions:
```
curl -H "Authorization: Bearer $TOKEN" \
  https://<host>/api/agm/sessions
```

Get candidates for a session:
```
curl -H "Authorization: Bearer $TOKEN" \
  https://<host>/api/agm/sessions/$ID/candidates
```

Cast a vote (candidate 123):
```
curl -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"candidate_id":123}' \
  https://<host>/api/agm/sessions/$ID/vote
```

View results:
```
curl -H "Authorization: Bearer $TOKEN" \
  https://<host>/api/agm/sessions/$ID/results
```


## Related Member Reports (FYI)
Members can also download annual PDFs that are often discussed during AGMs:
- Appropriation Account: GET /api/download-appropriation/{year}
- Financial Statements (Income & Expenditure + Balance Sheet): GET /api/download-financials/{year}
These appear in the app under Reports (/reports) with a year selector.


## Testing Checklist
- [ ] Create a draft session; add at least two positions with multiple candidates.
- [ ] Set status=open (or set a valid time window) and confirm it appears under /agm.
- [ ] As a member, open /agm/sessions/:id, vote for each position once; confirm UI blocks repeat votes and server returns 409 if forced.
- [ ] Refresh â€œLive Resultsâ€ and confirm tallies increase accurately and include zeroâ€‘vote candidates.
- [ ] Set status=closed and confirm voting is blocked (422).
- [ ] Review admin panel Votes relation for audit.


## Troubleshooting
- â€œVoting is closed for this sessionâ€ (422): Ensure status=open or current time within start/end window.
- â€œYou have already voted for <position>â€ (409): One vote per position is enforced; there is no reâ€‘vote.
- Session not visible on /agm: Confirm status=open or the time window is currently active; also ensure youâ€™re authenticated.
- Candidate not found (404): The candidate must be created under the same session.


## Future Enhancements
- Anonymous ballot storage with verifiable tallies
- Tieâ€‘break logic and runoff workflows in results view
- Auto-generate PDF Fatwa certificates from templates
- Push notifications when quorum is reached


---
If you have questions or need support, open an issue or contact the project maintainers.
