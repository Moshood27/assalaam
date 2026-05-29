# Qard Hasan (Interestâ€‘Free Loans) â€“ Implementation Guide

This document explains the endâ€‘toâ€‘end Qard Hasan loan implementation in this repository: data models, member/admin flows, guarantor logic, disbursement, repayment, notifications, and API reference.

Applicable date: 2026-04-01


## 1) Concept Overview
- Qard Hasan is an interestâ€‘free loan facility for members.
- Members can request a loan; depending on their assalaam Score and policy rules, they may need 2â€“3 guarantors.
- Admins review in Filament: Approve/Reject, optionally accept guarantors on behalf of members, and Disburse to the memberâ€™s inâ€‘app wallet.
- Repayment is strictly blocked until the loan is disbursed and becomes Active.


## 2) Data Model Snapshot
The relevant backend models are:

- QardHasan
  - Fields (key): user_id, qard_id_string, principal_amount, total_installments, per_installment, interval, admin_fee_flat, admin_fee_pct, paid_amount, status (pending | active | completed | cancelled), rejection_reason, approved_by, approved_at
  - Accessors: remaining_principal, progress_pct, is_completed, credited_amount, next_due_at
  - Relationships: belongsTo user, belongsTo approvedBy (User), hasMany repayments, belongsToMany guarantors (pivot)
  - Helpers: allGuarantorsAccepted(), pendingGuarantorCount(), generateInstallmentSchedule()

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
- Sixâ€‘month membership minimum: members must have â‰¥ 6 months in the system before requesting a loan.
- Base principal:
  - First loan: 5% of (Savings + Shares)
  - After completing first loan: 2 Ã— (Savings + Shares)
- assalaam Score limit boost (applies only after the first loan is completed):
  - Score â‰¥ 90 â†’ +15%
  - Score â‰¥ 80 â†’ +10%
  - Score â‰¥ 70 â†’ +5%
- Guarantor requirement from assalaam Score:
  - Instant approval when score â‰¥ instant threshold â†’ 0 guarantors
  - Low score â†’ 3 guarantors
  - Otherwise â†’ 2 guarantors

Endpoint to view memberâ€‘specific eligibility summary: GET /api/loans/eligibility


## 4) Member Flows (API)
- View my loans: GET /api/loans
- Check eligibility: GET /api/loans/eligibility
- Request a loan: POST /api/loans
  - Body:
    {
      "total_installments": 10,                 // required, integer â‰¥ 1
      "interval": "monthly",                   // one of: daily|weekly|monthly (caseâ€‘insensitive)
      "admin_fee_flat": 0,                      // optional, â‰¥ 0
      "admin_fee_pct": 0,                       // optional, 0â€“2 (%)
      "guarantor_ids": [12, 34],                // optional; used when guarantors are required
      "guarantor_memberships": ["AB123", "X9"] // optional; alternative to IDs (unique membership numbers)
    }

  - Validation & policy:
    - Must be in system for â‰¥ 6 months.
    - Only one open loan at a time (cannot have pending/active loan).
    - If guarantors required: 2â€“3 guarantors, none are defaulters, and member cannot select self.
    - When membership numbers are supplied, each must resolve to exactly one user.

  - Outcomes:
    - Instant approval path (0 guarantors): Loan status becomes active immediately, wallet is credited with Principal âˆ’ Fees, and notifications are sent to member/admins.
    - Standard path: Loan status is pending; guarantors are attached with pivot status=pending and unique tokens; notifications are sent to guarantors and admins.

- Repay loan: POST /api/loans/{id}/repay
  - Body:
    {
      "amount": 5000.00,                // required, â‰¥ 0.01; capped to remaining principal
      "source": "auto|wallet|paystack|flutterwave|bank_transfer|ussd", // optional; auto uses wallet if balance is enough
      "callback_url": "https://â€¦"      // optional, for gateway redirect
    }
  - Rules and behavior:
    - Repayment is blocked unless loan status is active (disbursed). Members cannot repay while pending or cancelled/completed.
    - Wallet path: Deducts balance immediately, creates success QardHasanRepayment, updates loan paid_amount and status=completed when fully repaid, creates WalletTransaction debit, emails receipt.
    - Paystack/Flutterwave paths: Creates pending QardHasanRepayment and returns authorization link/reference; provider webhook finalizes payment and updates loan totals.
    - Email validation is required for online gateway flows; wallet flow does not require email.


### Automated Repayment Hunters (Auto Recovery)
To ensure high repayment rates, the system includes an automated background worker that monitors members with overdue loans.

- **Trigger**: When a memberâ€™s wallet balance increases (e.g., via a card top-up or bank transfer to their Virtual Account), the system may dispatch the `AutoRecoverOverdueLoans` job.
- **Behavior**: 
  - The job calculates the memberâ€™s total overdue amount across all active loans.
  - If the member has a positive wallet balance, the system automatically debits the wallet and applies the funds to the oldest overdue installments first.
  - **Locks**: The process uses row-level database locks (`lockForUpdate`) on both the User and Loan records to prevent double-charging or race conditions.
  - **Notification**: Upon successful auto-recovery, the member receives a push notification and email acknowledging the automatic payment.
  - **Transparency**: These transactions are tagged in the wallet history with `auto_hunter: true` in the metadata.

## 5) Guarantors (Digital Acceptance)
- For nonâ€‘instant loans, selected guarantors are attached to the loan with pivot status=pending and a unique token; SMS/Push notify them to review and accept/decline in the app.
- Disbursement requires that all guarantors have accepted (status=accepted) and that at least two guarantors are present.
- Admin override: In Filament, admins can run â€œAccept Guarantorsâ€ to mark all attached guarantors as accepted (sets responded_at=now).
- Automated nudges: The system sends push reminders to pending guarantors twice daily and tracks nudge_count/last_nudged_at on the pivot.
- Autoâ€‘escalation: If a guarantor request stalls for 48h, it is autoâ€‘escalated (escalated_at is set) and admins are notified; members can also request escalation via POST /api/guarantor/loans/{id}/escalate.


## 6) Admin Workflows (Filament > Loans)
- Table highlights: Created, Member, Guarantors list, Loan ID, Principal, Credited, Paid, Approved By/At, Status with badges.
- Actions:
  - Approve: Sets approved_by/approved_at; does not disburse.
  - Reject: Requires a reason, marks status=cancelled, notifies the member (email + optional push).
  - Accept Guarantors: Admin override to mark all attached guarantors as accepted; use only when appropriate.
  - Disburse: Preconditions â†’ member has â‰¥ 6 months; all guarantors accepted (unless instant approval path). Credits member wallet with Principal âˆ’ Fees and sets status=active. Notifies member and all admins by email; also sends SMS/Push where available.
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
  - Member email: RepaymentReceiptUser for both wallet and webhookâ€‘finalized repayments.
  - Member SMS/Push: Acknowledges the repayment, shows remaining principal and reference.
- On rejection:
  - Member email + optional push with the reason provided by admin.

All outbound notifications are bestâ€‘effort and do not block core flows.


## 8) Security, Validation, and Integrity
- Transactions and locks:
  - Disbursement runs in a DB transaction.
  - Repayment uses SELECT â€¦ FOR UPDATE on the loan row to prevent races.
- Policy enforcement:
  - Sixâ€‘month membership, single open loan at a time, nonâ€‘defaulter checks, selfâ€‘guarantee blocked.
- Gateway safeguards:
  - Paystack/Flutterwave init requires valid member email; webhook handlers verify signatures and/or reâ€‘verify with providers, check currency/amount, and tolerate duplicates idempotently.
- Auditing:
  - ShariahAudit logs major events: create (instant/standard), repayment inits/completions.


## 9) Quick API Reference
- GET /api/loans â€” list the authenticated memberâ€™s loans (with repayments and guarantors)
- GET /api/loans/eligibility â€” show member eligibility, assalaam Score, required_guarantors, and boosted limits
- POST /api/loans â€” create a loan (see body schema above)
- POST /api/loans/{id}/repay â€” repay a loan (wallet, bank_transfer/ussd instructions, or gateway)
- GET /api/reports/loans/{id}/schedule â€” view your amortization schedule and next due installment
- Admin audit logs: GET /api/admin/reports/audit-trail?from=YYYY-MM-DD&to=YYYY-MM-DD&action=â€¦&user_id=â€¦&format=json|csv
- Webhooks (serverâ€‘side):
  - POST /api/webhooks/paystack â€” finalizes Paystack repayments (and other payments)
  - POST /api/webhooks/flutterwave â€” finalizes Flutterwave repayments (if enabled)

Notes:
- Exact route names may vary with routing setup, but they map to LoanController@index|eligibility|store|repay in this codebase.


## 10) Business Rules and Edge Cases
- Loan becomes Active only upon disbursement (or instant approval path). Members cannot repay while Pending.
- When paid_amount â‰¥ principal_amount, status moves to Completed.
- Repayment amounts are capped to the remaining principal to prevent overpayment.
- Admin fee support:
  - Flat fee (admin_fee_flat) and percentage fee (admin_fee_pct up to 2%).
  - Credited amount = principal âˆ’ (flat + principal Ã— pct/100).
- Guarantor requirements scale with assalaam Score; instant approval requires none and proceeds to immediate disbursement/activation.


## 11) Testing Tips
- Member request:
  - Ensure the test member has â‰¥ 6 months in the system and no open loans.
  - For guarantor path: pick 2â€“3 nonâ€‘defaulter users.
- Instant approval test:
  - Temporarily set a high assalaam Score for the member (or use a fixture) to trigger 0 guarantors and instant activation/disbursement.
- Repayment (wallet):
  - Top up wallet, then POST /api/loans/{id}/repay with { amount, source: "wallet" } and verify paid_amount and status transitions.
- Repayment (Paystack/Flutterwave):
  - Initialize with source and amount; complete via providerâ€™s test tools; confirm webhook updates repayment to success and loan totals.
- Admin Filament:
  - Try Approve â†’ Accept Guarantors â†’ Disburse; observe wallet credit and status=active.
  - Try Reject with a reason and verify member receives an email.


## 12) Future Enhancements
- Memberâ€‘facing guarantor response UI/endpoint improvements and reminders.
- Automated nudges to guarantors; escalation workflows for stalled requests.
- Detailed admin audit views and export for ShariahAudit logs.
- Additional repayment channels and installment scheduling helpers.
