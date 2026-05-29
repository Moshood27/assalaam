# Takaful (Member Welfare Pool) â€“ Feature Guide

Applicable date: 2026-04-04


## 1) Concept Overview
- Takaful is a cooperative, non-refundable welfare pool funded by small monthly member contributions (default â‚¦200/month).
- Purpose: If a member with an active Qard Hasan loan passes away or suffers a major loss, the system attempts to settle the memberâ€™s remaining principal from the pool, protecting both the cooperative and the memberâ€™s family.
- Scope: Only principal on active Qard Hasan loans is settled. Pool pays when it has enough balance to fully clear a given loanâ€™s outstanding principal.


## 2) Data Model Snapshot
The backend includes dedicated models and fields to support Takaful.

- TakafulPoolEntry
  - Ledger of the shared pool.
  - Fields: direction (credit|debit), amount, reference, meta (JSON), timestamps.
  - Helper: balance() â€“ returns current net balance (credits â€“ debits).

- TakafulContribution
  - Records each memberâ€™s monthly contribution by period (e.g., "2026-04").
  - Fields: user_id, period, amount, status (success|pending|failed), reference, meta, timestamps.
  - Unique constraint: (user_id, period) â€“ one row per member per period.

- User flags
  - users.deceased_at (nullable timestamp)
  - users.major_loss_at (nullable timestamp)
  - These are set by admins to trigger pool settlement attempts of active Qard Hasan loans.

- WalletTransaction (existing)
  - Used when debiting a memberâ€™s wallet for a contribution.
  - For Takaful debits, source=takaful_contribution and unique reference is recorded.

- QardHasan and QardHasanRepayment (existing)
  - Settlement creates a QardHasanRepayment for the outstanding principal, updates paid_amount, and moves status to completed if fully settled.

- ShariahAuditLog (existing)
  - Records key actions for transparency (e.g., takaful_settlement events).

Migrations added
- 2026_04_03_203500_create_takaful_pool_entries_table.php
- 2026_04_03_203600_create_takaful_contributions_table.php
- 2026_04_03_203700_add_takaful_flags_to_users.php


## 3) Configuration
- Config key: services.takaful.monthly_amount (float)
- Environment variable: TAKAFUL_MONTHLY_AMOUNT (default 200)
- File: backend/config/services.php

Example (.env)
- TAKAFUL_MONTHLY_AMOUNT=200


## 4) Automation & Scheduling
- Console Command: takaful:charge
  - Options:
    - --period=YYYY-MM
    - --amount=NUMBER
    - --user=ID
    - --dry-run
  - Description: Creates monthly Takaful contribution records (pending) for members to pay manually. Skips members already paid for that period. Previously debited wallets automatically, but now requires manual payment by members.

- Scheduler (backend/app/Console/Kernel.php)
  - Runs monthly on the 1st at 08:10 Africa/Lagos:
    - $schedule->command('takaful:charge')->monthlyOn(1, '08:10')->timezone('Africa/Lagos');


## 5) Member Flows (Frontend + API)
- Frontend route: /takaful (Member Welfare Pool)
  - Shows current period, monthly amount, whether paid this period, memberâ€™s lifetime contributions total, and the global pool balance.
  - Allows immediate "Pay now" if not yet paid this month.

- Member APIs (protected)
  - GET /api/takaful/summary
    - Returns: period, monthly_amount, paid_this_period (bool), my_total_contributions, pool_balance.
  - GET /api/takaful/contributions
    - Paginated list (page, per_page) of the memberâ€™s contributions (period, amount, status, reference, created_at).
  - POST /api/takaful/pay-now
    - Optional body: { period?: 'YYYY-MM', amount?: number }
    - Debits wallet immediately and credits pool; creates or updates the success record for that period.
    - Responses:
      - 200 success: { status: 'success', reference, balance, pool_balance }
      - 409 conflict: already paid for this period
      - 422 insufficient funds

Notes
- The monthly scheduler creates pending contribution records; members must pay manually via "Pay now".
- Automatic debiting from wallet is disabled to give members control over payment timing.


## 6) Admin Workflows (API)
- All endpoints require an authenticated admin (is_admin=true).

- GET /api/admin/takaful/summary
  - Query: period (defaults to current)
  - Returns pool_balance and contributions overview (count, sum, by_status) for the period.

- GET /api/admin/takaful/ledger
  - Query filters: page, per_page, direction, date_from, date_to, user_id (from meta->user_id)
  - Returns a paginated ledger with summary: credits, debits, net, pool_balance.

- POST /api/admin/takaful/charge
  - Body: { period?, amount?, user_id?, dry_run? }
  - Triggers batch charging (same internals as the console command). Returns counters (processed, created, charged total, skipped_existing, insufficient_funds, balance) and dry_run flag.

- POST /api/admin/takaful/mark-deceased
  - Body: { user_id: number, date?: ISO8601 }
  - Sets users.deceased_at and immediately attempts settlement of active Qard Hasan loans from the pool.

- POST /api/admin/takaful/mark-major-loss
  - Body: { user_id: number, date?: ISO8601 }
  - Sets users.major_loss_at and immediately attempts settlement of active Qard Hasan loans from the pool.


## 7) Settlement Logic (How payouts work)
- Trigger: Admin marks a member as deceased or suffered major loss.
- Engine: App\Services\TakafulService::settleMemberLoans(User, reason)
  - Finds the memberâ€™s QardHasan loans with status=active.
  - For each loan:
    - Computes remaining principal (principal_amount - paid_amount).
    - Checks the live pool balance; if enough to fully cover that loanâ€™s remainder, proceeds; otherwise, leaves the loan untouched (skipped_insufficient_pool).
    - Inside a database transaction: creates a QardHasanRepayment for the remaining amount, updates paid_amount, sets status to completed when fully covered, debits the pool via a TakafulPoolEntry (direction=debit), and writes a ShariahAuditLog entry.
  - Returns a summary: list of loans settled or skipped, total_settled, pool_before, pool_after.

Important notes
- Partial settlements for a single loan are not attempted; the pool must be sufficient to clear the loanâ€™s remaining principal at the time.
- Pool balance is recalculated before each loan to reflect prior debits within the same run.


## 8) Charging Logic (How monthly contributions are processed)
- Engine: App\Services\TakafulService::chargeMonthly(period, amount?, userId?, dryRun=false)
  - Selects all non-admin users without deceased_at (admins are skipped by default) and optionally a single user when userId is set.
  - Skips any user who already has a success contribution for the given period.
  - For each selected user:
    - If dryRun: increments counters without writing.
    - Else: within a transaction, verifies no success exists, then creates/updates a pending row (manual_payment_policy).
  - Returns totals and the pool balance.
- Note: Automatic wallet debits and automated retries on top-up are disabled. Members must initiate payment via the "Pay now" feature.

- Member-initiated one-off payment: App\Services\TakafulService::payNow(user, period?, amount?) follows similar steps for a single member.

Idempotency
- TakafulContribution has a unique (user_id, period) constraint; updates are done via updateOrCreate to avoid duplicates.
- WalletTransaction references are unique, preventing duplicate debits.


## 9) Security & Validation
- All member endpoints require sanctum auth and the inactivity middleware, matching the appâ€™s standard protection.
- Admin endpoints require is_admin=true.
- Inputs are validated (e.g., period format YYYY-MM, numeric amount, existing user_id).
- Pool ledger and contributions queries allow optional filters but are read-only; writes are controlled and transactional.


## 10) Member & Admin UI Touchpoints
- Member: /takaful page (Vue) displays the summary and the contribution history, with a Pay now action.
- Admin: Filament/REST usage assumed for now via the admin API routes; a UI can be added later to visualize pool ledger and actions.


## 11) Quick API Reference
Member (requires auth)
- GET  /api/takaful/summary
- GET  /api/takaful/contributions?per_page=15&page=1
- POST /api/takaful/pay-now  { period?: 'YYYY-MM', amount?: number }

Admin (requires is_admin)
- GET  /api/admin/takaful/summary?period=YYYY-MM
- GET  /api/admin/takaful/ledger?direction=credit|debit&date_from=YYYY-MM-DD&date_to=YYYY-MM-DD&user_id=ID
- POST /api/admin/takaful/charge  { period?, amount?, user_id?, dry_run? }
- POST /api/admin/takaful/mark-deceased  { user_id, date? }
- POST /api/admin/takaful/mark-major-loss  { user_id, date? }

Console
- php artisan takaful:charge --period=2026-04 --dry-run


## 12) Worked Examples
A. Monthly batch on the 1st
1) Scheduler runs takaful:charge for April (2026-04).
2) Pending contribution records are created for all eligible members.
3) Members receive a notice (or see the pending status in their app) and click "Pay now" to contribute.

B. Manual pay-now by a member
1) Member opens /takaful and clicks Pay now.
2) Server debits wallet immediately and credits the pool; success is shown along with a reference.

C. Settlement after marking a member as deceased
1) Admin calls POST /api/admin/takaful/mark-deceased { user_id }.
2) System sets users.deceased_at, then attempts to settle all active Qard Hasan loans from the pool.
3) For each loan with pool coverage, a repayment is created with source TAKAFUL_PAYOUT_*, the loan is completed, and the pool is debited.


## 13) Testing Tips
- To simulate batch charging without writes: php artisan takaful:charge --dry-run
- To test a single user batch: php artisan takaful:charge --user=123
- To test member flow quickly:
  - Credit wallet (e.g., via a safe test top-up path or DB in a dev environment).
  - Call POST /api/takaful/pay-now with the current period.
  - Verify: WalletTransaction (debit), TakafulContribution (success), TakafulPoolEntry (credit), and /takaful UI updates.
- To test settlement:
  - Ensure the pool has enough balance (e.g., multiple members paid).
  - Create an active Qard Hasan loan with outstanding principal.
  - Call POST /api/admin/takaful/mark-deceased { user_id } (or mark-major-loss).
  - Verify: Repayment created, loan status possibly completed, pool debit recorded, ShariahAuditLog has an entry.


## 14) Glossary
- Takaful: Cooperative welfare scheme based on mutual assistance.
- Pool: The collective fund against which settlements are paid.
- Contribution (Takaful): The monthly non-refundable payment made by members.
- Settlement: Paying off the outstanding principal of a memberâ€™s Qard Hasan loan from the pool due to qualifying events.
- Period: A month token in YYYY-MM format.


## 15) Where to Extend Next
- Add admin UI pages to visualize the pool ledger, filter by member, and trigger manual batch charges.
- Add automated retries to attempt charging pending contributions after wallet top-ups.
- Notify guarantors or next-of-kin on settlement outcomes (policy-dependent).
- Introduce configurable per-member policy toggles or exclusions where required by the cooperativeâ€™s byâ€‘laws.
- Add export endpoints (CSV/PDF) for pool ledger and monthly summaries.
