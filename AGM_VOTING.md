# Digital AGM & Member Voting

This document explains how the cooperative’s Annual General Meeting (AGM) and in‑app Member Voting work across the system: data model, APIs, frontend UX, and the Filament admin panel.

Last updated: 2026‑03‑21


## Goals
- Enable democratic elections of committee/board positions digitally.
- Let members review positions and candidates, vote once per position, and see live tallies.
- Give admins a simple panel to configure AGM sessions, manage candidates, and monitor votes.


## Roles at a Glance
- Member (mobile/web app):
  - Browse active AGM sessions (/agm)
  - View positions and candidates for a session (/agm/sessions/:id)
  - Cast exactly one vote per position per session
  - View live results (vote tallies) per position
- Admin (Filament panel):
  - Create/update AGM sessions (name, status, optional start/end window)
  - Add/manage candidates per session and position
  - View votes (read‑only) and vote counts per candidate


## Data Model
The feature uses three tables/models with constraints that enforce one‑vote‑per‑position per member:

- AgmSession
  - Fields: id, name, status (draft|open|closed), start_at, end_at, timestamps
  - Computed attributes: is_open (true if status=open OR current time is within [start_at, end_at]), is_within_window
  - Relations: candidates(), votes()
- AgmCandidate
  - Fields: id, session_id (FK), name, position, manifesto, photo_url, timestamps
  - Relations: session(), votes()
- AgmVote
  - Fields: id, session_id (FK), position, user_id (FK), candidate_id (FK), timestamps
  - Unique constraint: (session_id, position, user_id)
  - Relations: session(), candidate(), user()

These are defined in:
- backend/app/Models/AgmSession.php
- backend/app/Models/AgmCandidate.php
- backend/app/Models/AgmVote.php
- backend/database/migrations/2026_03_21_012000_create_agm_sessions_table.php
- backend/database/migrations/2026_03_21_012100_create_agm_candidates_table.php
- backend/database/migrations/2026_03_21_012200_create_agm_votes_table.php


## Voting Rules & Lifecycle
- Sessions can be “open” explicitly (status=open) OR implicitly by a schedule window where now ∈ [start_at, end_at].
- Members can cast one vote per position per session. Once recorded, it cannot be changed from the app.
- Server validates that the candidate belongs to the given session.
- Server blocks voting if:
  - Session is closed or out of window → 422 Voting is closed
  - Member already voted for that position → 409 You have already voted for <position>
- Results are live tallies grouped by position and candidate. Positions/candidates with zero votes are still listed with 0.


## Backend APIs (Member‑facing)
All endpoints are protected with Sanctum and the inactivity middleware. Send the member token with Authorization: Bearer <token>.

Controller: backend/app/Http/Controllers/Api/AgmController.php
Routes: backend/routes/api.php

- GET /api/agm/sessions
  - Returns latest active/open sessions (status=open or within the configured start/end window).
  - 200 OK: [ { id, name, status, start_at, end_at, is_open, is_within_window, ... }, ... ]

- GET /api/agm/sessions/{id}/candidates
  - Returns candidates grouped by position for the session, plus the caller’s voted_candidate_id per position.
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
  - Returns aggregated votes per position.
  - 200 OK: {
      session: { ... },
      results: {
        "President": [ { candidate_id, candidate_name, votes }, ... ],
        "Secretary": [ ... ]
      }
    }

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
  - Prevents voting again if you already voted for a position (shows “Voted”).
  - Confirms selection before submission (native/overridden alert/confirm pattern).
  - Live results section fetches and displays tallies per position; can refresh.

UI safeguards:
- Button disabled if already voted for that position.
- Errors are surfaced with readable messages from API (409, 422, etc.).


## Admin Panel (Filament)
Admins manage AGM via Filament resources in the backend admin panel.

- AgmSessionResource
  - Create/edit sessions with: name, status (draft|open|closed), start_at, end_at.
  - Filters and badges indicate status; can bulk delete, etc.
  - Relations:
    - Candidates (CandidatesRelationManager)
      - CRUD for candidates with name, position, manifesto, photo URL
      - Shows live votes_count per candidate
    - Votes (VotesRelationManager)
      - Read‑only list of recorded votes: position, candidate, voter, timestamp

Usage guidance:
1) Create a new session (status=draft while you add candidates).
2) Add candidates for each position (ensure position names are consistent, e.g., “President”, “Secretary”).
3) Open the session by either:
   - Setting status to open; or
   - Setting a [start_at, end_at] window that includes the current time.
4) Members can now vote in the app. Monitor votes and results from the panel.
5) Close after the election by setting status=closed or ending the time window.


## Security, Privacy, and Integrity
- Authentication: All member endpoints require a valid Sanctum token.
- One‑vote enforcement: Backed by database unique constraint (session_id, position, user_id) plus controller checks.
- Auditability: Admin panel exposes read‑only view of votes. Consider limiting access to authorized election officers.
- Privacy: Although the system stores which user voted for whom (for audit), member‑facing results are aggregated only. If stronger secrecy is required, a future enhancement could pseudonymize votes while preserving constraints and tallies.


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
- [ ] Refresh “Live Results” and confirm tallies increase accurately and include zero‑vote candidates.
- [ ] Set status=closed and confirm voting is blocked (422).
- [ ] Review admin panel Votes relation for audit.


## Troubleshooting
- “Voting is closed for this session” (422): Ensure status=open or current time within start/end window.
- “You have already voted for <position>” (409): One vote per position is enforced; there is no re‑vote.
- Session not visible on /agm: Confirm status=open or the time window is currently active; also ensure you’re authenticated.
- Candidate not found (404): The candidate must be created under the same session.


## Future Enhancements
- Anonymous ballot storage with verifiable tallies
- Tie‑break logic and runoff workflows in results view
- Exportable CSV/PDF of results for admins
- Eligibility gating (e.g., only members in good standing may vote)


---
If you have questions or need support, open an issue or contact the project maintainers.