# Qard Hasan (Interest‑Free Loans) – Implementation Guide

This document explains the end‑to‑end Qard Hasan loan implementation in this repository: data models, member/admin flows, guarantor logic, disbursement, repayment, notifications, and API reference.

Applicable date: 2026-04-01


## 1) Concept Overview
- Qard Hasan is an interest‑free loan facility for members.
- Members can request a loan; depending on their Coop Score and policy rules, they may need 2–3 guarantors from different branches.
- Admins review in Filament: Approve/Reject, optionally accept guarantors on behalf of members, and Disburse to the member’s in‑app wallet.
- Repayment is strictly blocked until the loan is disbursed and becomes Active.


## 2) Data Model Snapshot
The relevant backend models are:

- QardHasan
  - Fields (key): user_id, qard_id_string, principal_amount, total_installments, per_installment, interval, admin_fee_flat, admin_fee_pct, paid_amount, status (pending | active | completed | cancelled), rejection_reason, approved_by, approved_at
  - Accessors: remaining_principal, progress_pct, is_completed, credited_amount
  - Relationships: belongsTo user, belongsTo approvedBy (User), hasMany repayments, belongsToMany guarantors (pivot)
  - Helpers: allGuarantorsAccepted(), pendingGuarantorCount()

- QardHasanRepayment
  - Fields: qard_hasan_id, amount, reference, status (pending | success), paid_at
  - Created when wallet repayment is made (status=success) or when a gateway repayment is initialized (status=pending) and later finalized via webhook.

- qard_hasan_guarantors (pivot table)
  - Fields: qard_hasan_id, guarantor_id, status (pending | accepted | declined), token, responded_at
  - Indices: (qard_hasan_id, guarantor_id), (qard_hasan_id, status)
  - Used to track digital acceptance of guarantors.

- WalletTransaction
  - Records member wallet credits/debits (e.g., disbursements, loan repayments from wallet).

- User (selected fields/behaviors referenced)
  - balance (wallet), branch_id, is_admin, is_defaulter, fcm_token/device_token, email/phone
  - monthsInSystem(), savingsSharesEligibility(), adjustedLoanEligibility(), hasCompletedLoan()

- ShariahAuditLog
  - Audits sensitive loan events (creation paths, gateway inits, repayments, instant approvals).


## 3) Eligibility and Principal Calculation
- Six‑month membership minimum: members must have ≥ 6 months in the system before requesting a loan.
- Base principal:
  - First loan: 5% of (Savings + Shares)
  - After completing first loan: 2 × (Savings + Shares)
- Coop Score limit boost (applies only after the first loan is completed):
  - Score ≥ 90 → +15%
  - Score ≥ 80 → +10%
  - Score ≥ 70 → +5%
- Guarantor requirement from Coop Score:
  - Instant approval when score ≥ instant threshold → 0 guarantors
  - Low score → 3 guarantors
  - Otherwise → 2 guarantors

Endpoint to view member‑specific eligibility summary: GET /api/loans/eligibility


## 4) Member Flows (API)
- View my loans: GET /api/loans
- Check eligibility: GET /api/loans/eligibility
- Request a loan: POST /api/loans
  - Body:
    {
      "total_installments": 10,                 // required, integer ≥ 1
      "interval": "monthly",                   // one of: daily|weekly|monthly (case‑insensitive)
      "admin_fee_flat": 0,                      // optional, ≥ 0
      "admin_fee_pct": 0,                       // optional, 0–2 (%)
      "guarantor_ids": [12, 34],                // optional; used when guarantors are required
      "guarantor_memberships": ["AB123", "X9"] // optional; alternative to IDs (unique membership numbers)
    }

  - Validation & policy:
    - Must be in system for ≥ 6 months.
    - Only one open loan at a time (cannot have pending/active loan).
    - If guarantors required: 2–3 guarantors, from different branches, none are defaulters, and member cannot select self.
    - When membership numbers are supplied, each must resolve to exactly one user.

  - Outcomes:
    - Instant approval path (0 guarantors): Loan status becomes active immediately, wallet is credited with Principal − Fees, and notifications are sent to member/admins.
    - Standard path: Loan status is pending; guarantors are attached with pivot status=pending and unique tokens; notifications are sent to guarantors and admins.

- Repay loan: POST /api/loans/{id}/repay
  - Body:
    {
      "amount": 5000.00,                // required, ≥ 0.01; capped to remaining principal
      "source": "auto|wallet|paystack|flutterwave", // optional; auto uses wallet if balance is enough
      "callback_url": "https://…"      // optional, for gateway redirect
    }
  - Rules and behavior:
    - Repayment is blocked unless loan status is active (disbursed). Members cannot repay while pending or cancelled/completed.
    - Wallet path: Deducts balance immediately, creates success QardHasanRepayment, updates loan paid_amount and status=completed when fully repaid, creates WalletTransaction debit, emails receipt.
    - Paystack/Flutterwave paths: Creates pending QardHasanRepayment and returns authorization link/reference; provider webhook finalizes payment and updates loan totals.
    - Email validation is required for online gateway flows; wallet flow does not require email.


## 5) Guarantors (Digital Acceptance)
- For non‑instant loans, selected guarantors are attached to the loan with pivot status=pending and a unique token; SMS/Push notify them to review and accept/decline in the app.
- Disbursement requires that all guarantors have accepted (status=accepted) and that at least two guarantors are present.
- Admin override: In Filament, admins can run “Accept Guarantors” to mark all attached guarantors as accepted (sets responded_at=now).


## 6) Admin Workflows (Filament > Loans)
- Table highlights: Created, Member, Guarantors list, Loan ID, Principal, Credited, Paid, Approved By/At, Status with badges.
- Actions:
  - Approve: Sets approved_by/approved_at; does not disburse.
  - Reject: Requires a reason, marks status=cancelled, notifies the member (email + optional push).
  - Accept Guarantors: Admin override to mark all attached guarantors as accepted; use only when appropriate.
  - Disburse: Preconditions → member has ≥ 6 months; all guarantors accepted (unless instant approval path). Credits member wallet with Principal − Fees and sets status=active. Notifies member and all admins by email; also sends SMS/Push where available.
- Deletion Guard: A loan cannot be deleted if any repayment exists or any amount has been paid.


## 7) Notifications
- On loan request (pending path):
  - Admin email: LoanRequestedAdminNotification to configured admin emails.
  - Guarantor SMS/Push: Prompt to accept/decline in app.
- On disbursement/instant approval:
  - Member email: LoanDisbursedUser, with credited amount details.
  - Admin email: LoanDisbursedAdminNotification to all admins.
  - Member SMS/Push: Disbursed amount and new wallet balance. Admin Push is also attempted in instant path.
- On repayment:
  - Member email: RepaymentReceiptUser for both wallet and webhook‑finalized repayments.
  - Member SMS/Push: Acknowledges the repayment, shows remaining principal and reference.
- On rejection:
  - Member email + optional push with the reason provided by admin.

All outbound notifications are best‑effort and do not block core flows.


## 8) Security, Validation, and Integrity
- Transactions and locks:
  - Disbursement runs in a DB transaction.
  - Repayment uses SELECT … FOR UPDATE on the loan row to prevent races.
- Policy enforcement:
  - Six‑month membership, single open loan at a time, guarantor branch diversity and non‑defaulter checks, self‑guarantee blocked.
- Gateway safeguards:
  - Paystack/Flutterwave init requires valid member email; webhook handlers verify signatures and/or re‑verify with providers, check currency/amount, and tolerate duplicates idempotently.
- Auditing:
  - ShariahAudit logs major events: create (instant/standard), repayment inits/completions.


## 9) Quick API Reference
- GET /api/loans — list the authenticated member’s loans (with repayments and guarantors)
- GET /api/loans/eligibility — show member eligibility, Coop Score, required_guarantors, and boosted limits
- POST /api/loans — create a loan (see body schema above)
- POST /api/loans/{id}/repay — repay a loan (wallet or gateway)
- Webhooks (server‑side):
  - POST /api/webhooks/paystack — finalizes Paystack repayments (and other payments)
  - POST /api/webhooks/flutterwave — finalizes Flutterwave repayments (if enabled)

Notes:
- Exact route names may vary with routing setup, but they map to LoanController@index|eligibility|store|repay in this codebase.


## 10) Business Rules and Edge Cases
- Loan becomes Active only upon disbursement (or instant approval path). Members cannot repay while Pending.
- When paid_amount ≥ principal_amount, status moves to Completed.
- Repayment amounts are capped to the remaining principal to prevent overpayment.
- Admin fee support:
  - Flat fee (admin_fee_flat) and percentage fee (admin_fee_pct up to 2%).
  - Credited amount = principal − (flat + principal × pct/100).
- Guarantor requirements scale with Coop Score; instant approval requires none and proceeds to immediate disbursement/activation.


## 11) Testing Tips
- Member request:
  - Ensure the test member has ≥ 6 months in the system and no open loans.
  - For guarantor path: pick 2–3 non‑defaulter users from distinct branches.
- Instant approval test:
  - Temporarily set a high Coop Score for the member (or use a fixture) to trigger 0 guarantors and instant activation/disbursement.
- Repayment (wallet):
  - Top up wallet, then POST /api/loans/{id}/repay with { amount, source: "wallet" } and verify paid_amount and status transitions.
- Repayment (Paystack/Flutterwave):
  - Initialize with source and amount; complete via provider’s test tools; confirm webhook updates repayment to success and loan totals.
- Admin Filament:
  - Try Approve → Accept Guarantors → Disburse; observe wallet credit and status=active.
  - Try Reject with a reason and verify member receives an email.


## 12) Future Enhancements
- Member‑facing guarantor response UI/endpoint improvements and reminders.
- Automated nudges to guarantors; escalation workflows for stalled requests.
- Detailed admin audit views and export for ShariahAudit logs.
- Additional repayment channels and installment scheduling helpers.
