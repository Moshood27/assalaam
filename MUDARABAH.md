# Mudarabah (Pooled Investment Projects) â€“ Flow Guide

This document explains how Mudarabah (pooled investment) works in the Cooperative application: data models, member/admin flows, payment paths, and how profits and management fees are represented.

Applicable date: 2026-03-30


## 1) Concept Overview
- Mudarabah is a pooled investment where the Cooperative acts as the Mudarib (manager) and members are the capital providers (investors).
- The Cooperative may create projects (e.g., Poultry Farm, Real Estate). Members can buy â€œsharesâ€ in these projects by paying into a regular scheme and tagging that payment to a project.
- Profits from a project are recorded and a management fee (e.g., 10%) is deducted before distributing the net profits to members proportionally to their invested amounts.


## 2) Data Model Snapshot
The relevant backend models are:

- Project
  - Fields: name, description, target_amount, management_fee_percent, active, started_at, closed_at
  - Relationships: hasMany investments, hasMany profits

- Contribution
  - Represents a member payment into a scheme. Can be linked to a project via project_id.
  - Fields: user_id, scheme_id, project_id (nullable), amount, reference, status (pending | success | failed)
  - Behavior: When a Contribution with a project_id becomes success, a ProjectInvestment is auto-created (idempotent).

- ProjectInvestment
  - Represents the investorâ€™s â€œshareâ€ entry in the project, created from a successful, project-linked Contribution.
  - Fields: user_id, project_id, contribution_id, amount, reference

- ProjectProfit
  - Used by admins to record profit events for a project and to compute/record management fee and net distributable amounts.
  - Fields: project_id, gross_profit, management_fee_percent, management_fee_amount, net_distributable, note

- WalletTransaction
  - For member wallet credit/debit, used when allocating from wallet to schemes or topping up the wallet.


## 3) Member Investment Flows
Members can invest in a project in two ways:

### A. Online Checkout (Paystack/Flutterwave)
1. Member goes to Make Payment (/pay in the frontend) and adds one or more scheme items. To tag a project, the member selects a Project for any item they want invested.
2. Frontend calls POST /api/initiate-payment with a payload like:
   {
     "items": [
       { "scheme_id": 1, "project_id": 3, "amount": 5000 },
       { "scheme_id": 2, "amount": 3000 }
     ],
     "callback_url": "https://app.example.com/payment-callback"
   }
3. Server creates pending Contribution rows for each item (ensures server-validated amounts and optional project reference), returns a redirect URL to the payment provider.
4. Member completes checkout on the provider. The app confirms success via webhooks:
   - Paystack: POST /api/webhooks/paystack
   - Flutterwave: POST /api/webhooks/flutterwave
   The server verifies the transaction and total amount for the reference, then marks the created Contributions as success.
5. When each project-linked Contribution moves to success, the Contribution modelâ€™s boot hooks create a ProjectInvestment for that user and project (idempotent, one per contribution).
6. Notifications are sent (email/push/SMS where available). The Passbook (scheme totals) is updated by virtue of the Contributions being recorded as success. The investment record is visible under the relevant project.

Notes:
- If a Paystack webhook arrives with no matching pending Contributions for the reference, it is treated as a wallet top-up (via dedicated virtual account or direct charge) and credited to the memberâ€™s wallet instead.
- Minimum per-scheme amounts are enforced; projects must be active to accept investments.

### B. Wallet Allocation (with Transaction PIN)
1. Member first tops up their wallet (e.g., via Paystack/Flutterwave checkout or dedicated virtual account). Wallet top-ups are recorded as WalletTransaction credit entries.
2. From /pay, member chooses â€œPay from walletâ€ and enters their 4-digit Transaction PIN.
3. Frontend calls POST /api/wallet/allocate with:
   {
     "items": [
       { "scheme_id": 1, "project_id": 3, "amount": 5000 },
       { "scheme_id": 2, "amount": 3000 }
     ],
     "pin": "1234"
   }
4. Server verifies the PIN and sufficient wallet balance, then:
   - Creates Contribution rows with status success (immediate, no external checkout), including project_id if provided.
   - Decrements member wallet balance and records a WalletTransaction debit entry referencing the distribution.
   - Auto-creates ProjectInvestment rows for any project-linked Contributions via the Contribution model hooks.


## 4) Admin Workflows (Filament)
- Projects
  - Path: Admin > Investments > Projects
  - Create/Edit Project: Set name, description, target amount, management_fee_percent, active status, and optional start/close dates.

- Project Investments (read-only list)
  - Path: Admin > Investments > Project Investments
  - Shows member, project, amount, and reference for each investment entry auto-created from successful Contributions.

- Contributions
  - Path: Admin > Contributions
  - Create supports a repeater to enter multiple scheme lines with optional Project per line.
  - Edit shows single record fields (including Project if set). Changing status to success for a project-linked Contribution will create a ProjectInvestment.

- Project Profits
  - Although there is a ProjectProfit model and migration, profit distribution to investors is intentionally decoupled and can be implemented as a later step.
  - Admins can record profit events per project using this model (via seeding or future UI), store management_fee_percent and computed amounts, and then run a distribution process (future/extended feature) to allocate net_distributable based on investor proportions.


## 5) Passbook & Member Views
- Passbook (/passbook) shows scheme-based summaries: brought-forward, monthly totals, and overall totals based on successful Contributions.
- Project-specific investments are not listed line-by-line in the Passbook, but the link exists via Contribution.project_id and dedicated endpoints:
  - GET /api/projects â€“ list active projects for members
  - GET /api/projects/{id} â€“ project details
  - GET /api/projects/{id}/investments â€“ memberâ€™s investments in that project (includes total_invested)


## 6) Payment, Security, and Idempotency Highlights
- Unique References: Each checkout or wallet allocation generates a unique reference used to reconcile all rows.
- Webhook Verification: Paystack and Flutterwave webhooks validate signatures and then re-verify transactions server-side to prevent spoofing.
- Amount Validation: The server computes expected totals from pending Contributions for a reference and rejects mismatches.
- Project Activation Check: Project must be active to accept new investments; this is enforced both on online checkout initiation and wallet allocation.
- Transaction PIN: Required for wallet allocations; PIN must be set and verified before debiting the wallet.
- Idempotency:
  - Wallet credits/debits are recorded with references; duplicates are skipped where appropriate.
  - ProjectInvestment creation is idempotent per contribution_id.
  - Contribution status transitions are handled safely; repeated webhook calls are tolerated.


## 7) Quick API Reference
- List Projects: GET /api/projects
- Project Details: GET /api/projects/{id}
- My Investments in a Project: GET /api/projects/{id}/investments
- Transparency Dashboard (Portfolio): GET /api/transparency
- Start Payment (Online Checkout): POST /api/initiate-payment
  - items[].scheme_id (required)
  - items[].project_id (optional, must refer to active project)
  - items[].amount (required)
  - callback_url (optional)
- Verify Payment Redirect (Paystack): POST /api/verify-payment
  - reference, gateway=paystack (optional, defaults to paystack)
- Allocate from Wallet: POST /api/wallet/allocate
  - items[] (same structure as above), pin (required 4 digits)
- Wallet: GET /api/wallet; GET /api/wallet/transactions; POST /api/wallet/topup/initiate
- Webhooks (server-side): POST /api/webhooks/paystack, POST /api/webhooks/flutterwave


## 8) Worked Example
1. Admin creates â€œPoultry Farm 2026â€ with management_fee_percent=10, active=true.
2. Member selects Scheme â€œInvestmentsâ€ and Project â€œPoultry Farm 2026â€, amount â‚¦50,000, then completes Paystack checkout.
3. Webhook verifies the payment and marks the linked Contribution success.
4. The system auto-creates a ProjectInvestment for the member with amount â‚¦50,000 on Poultry Farm 2026; Passbook shows â‚¦50,000 under the chosen scheme for that month; Admin can see the investment under Project Investments.


## 9) Profit Booking and Distribution (Current State)
- Admin can record a ProjectProfit with gross_profit and a management_fee_percent (often equal to the projectâ€™s setting).
- The system stores management_fee_amount and net_distributable on that profit record.
- Distribution of net_distributable to members pro-rata (after the management fee) is implemented via a queued job DistributeProjectProfit. From Filament > Investments > Project Profits, use the Distribute action on a profit to:
  - Compute each memberâ€™s share based on their proportion of total invested up to the profit timestamp (rounding fairly to kobo).
  - Create ProjectProfitPayout records per member and credit their wallet balances with matching WalletTransaction entries.
  - Send in-app notifications (ProjectProfitDistributed) and a best-effort push notification.
  - Ensure idempotency by skipping if payouts already exist for the profit.


## 10) Testing Tips
- Use wallet allocation for fast, end-to-end tests without an external gateway:
  - Top up wallet via POST /api/wallet/topup/initiate (then simulate webhook or directly adjust balance in a safe test environment).
  - Call POST /api/wallet/allocate with items including project_id and a valid 4-digit PIN.
  - Confirm: Contributions created (status=success), WalletTransaction debit recorded, ProjectInvestment entry created.

- For full gateway flow (staging):
  - Initiate payment via POST /api/initiate-payment and complete the hosted checkout.
  - Confirm via providerâ€™s test webhook that Contributions are marked success and investments are created.


## 11) Glossary
- Scheme: A savings/investment category into which members pay.
- Contribution: A specific scheme payment (transaction). May be linked to a project.
- Project: A pooled investment opportunity (Mudarabah) managed by the Cooperative.
- ProjectInvestment: The memberâ€™s recorded stake in a project (created from a successful, project-linked Contribution).
- ProjectProfit: Recorded profit for a project including computed management fee and net amount distributable to investors.
- Management Fee: Percentage of project profit taken by the Cooperative before distributing net profits.
- Wallet: Memberâ€™s in-app balance used to allocate funds into schemes without external checkout.


## 12) Where to Extend Next
- Build a Filament UI for ProjectProfit creation and a background job to distribute net_distributable to investors by proportion of total invested.
- Add member-facing pages that summarize each projectâ€™s performance and the memberâ€™s realized/expected profit shares.
- Add notifications for profit distribution events.
