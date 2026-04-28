# Loan Penalty System – Implementation Guide

This document explains the Loan Penalty system designed to discourage defaults by imposing a mandatory wait period before a member can apply for a new loan.

## 1) Overview
The system automatically tracks how long a member remains in default on a loan. Once the default is cleared, the member must wait for a period exactly equal to the duration they were in default before they are eligible for another loan.

## 2) Key Features
- **Automatic Triggering**: A penalty record is created as soon as a loan is marked as defaulted.
- **Dynamic Duration**: The penalty wait time is not a fixed number of months; it matches the exact duration of the default (down to the minute).
- **Graceful Transitions**: The system handles transitions from "Pending Clear" (active default) to "Active Penalty" (wait period) and finally to "Cleared".
- **Admin Visibility**: A dedicated report in the admin panel allows administrators to monitor penalized members and filter by branch.

## 3) How It Works

### Step 1: Default Trigger
When a loan's `defaulted_at` timestamp is set (usually by the `SendDefaultLoanReminders` command or manual admin action), the system:
1.  Creates a new `LoanPenalty` record for the user.
2.  Sets the `default_started_at` to the current time.
3.  The status of this penalty is "Pending Clear".

### Step 2: Clearing the Default
When the member clears their default (by fully repaying the loan or by an admin manually clearing the status), the system:
1.  Calculates the duration between `default_started_at` and now.
2.  Sets `default_cleared_at` to now.
3.  Calculates `penalty_until` by adding the default duration to the current time.
4.  Updates the `User` model's `loan_penalty_until` field.

### Step 3: Enforcement
During loan eligibility checks (`GET /api/loans/eligibility`) and loan submissions (`POST /api/loans`), the system checks the `loan_penalty_until` field on the user.
- If the current time is before `loan_penalty_until`, the member is blocked from applying.
- The API response provides a clear message indicating exactly how long they must wait (e.g., "You are currently under a loan penalty... You can apply again in 2 months, 3 days, 4 hours").

## 4) Admin Report (Filament)
Admins can access the **Loan Penalties** resource in the Filament dashboard.

### Report Columns:
- **Member**: The user under penalty.
- **Branch**: The member's branch (filterable).
- **Default Started At**: When the default began.
- **Default Cleared At**: When the default was resolved (empty if still defaulted).
- **Months/Days Defaulted**: The formatted duration of the default.
- **Wait Until**: The date when the penalty expires.
- **Status**: Visual badges indicating "Pending Clear" (Red), "Active Penalty" (Yellow), or "Expired" (Green).

### Filters:
- **Branch**: View penalties for specific branches.
- **Active Penalties Only**: Toggle to see only those currently blocked from applying for loans.

## 5) Data Model: `LoanPenalty`
- `user_id`: Reference to the member.
- `qard_hasan_id`: Reference to the loan that caused the penalty.
- `default_started_at`: Timestamp when default was first detected.
- `default_cleared_at`: Timestamp when default was cleared.
- `penalty_until`: Timestamp when the member can apply for a loan again.
- `months_defaulted`: (Legacy/Summary) Decimal representation of months.

## 6) Retroactive Sync & Maintenance
A dedicated Artisan command is available to ensure all records (including migrated data) are correctly tracked:

```bash
php artisan loans:sync-penalties
```

This command performs the following:
1.  **Missing Records**: Identifies defaulted loans that don't have a `LoanPenalty` entry and creates them.
2.  **Stray Penalties**: Finds open penalty records for loans that are no longer defaulted and completes them.
3.  **Self-Healing**: This logic is also integrated into the `loans:send-default-reminders` command, allowing the system to automatically repair any missing penalty data during its regular schedule.

## 7) Technical Notes
- **User Sync**: The `User` model has a `loan_penalty_until` column which is automatically kept in sync by `LoanPenalty` model observers (booted hook). This ensures high performance during API checks.
- **Precise Timing**: Carbon's precision is used for all calculations to ensure "exact months, days, and time" as requested.
- **Commands**:
    - `SendDefaultLoanReminders`: Flags defaults and triggers penalty sync.
    - `SyncLoanPenalties`: Dedicated tool for retroactive fixes and migrated data support.
    - `LoansHunterSweep`: Processes overdue payments.

---
*Last updated: April 2026*
