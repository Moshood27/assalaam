# Amanah/AS-SALAAM System Migration Guide

This document provides a comprehensive guide for administrators to transition legacy records (Excel/Paper) into the digital platform. It covers the **"Delete and Reset"** strategy, ensuring a clean, accurate, and professional member experience.

---

## 1. The "Delete and Reset" Strategy

During the testing/demo phase, members may have created "Test" transactions or loans. To ensure a professional launch, we use a **Delete and Reset** approach:

1.  **Clean Sweep:** Removes all non-migration data (transactions, loans, etc.) created during the demo.
2.  **Rebuild from Truth:** Re-imports official records from your Excel "Source of Truth."
3.  **Balance Sync:** Automatically recalculates the member's dashboard balance to match the sum of their imported history.

---

## 2. Preparation: Excel Templates

Before importing, ensure your Excel files match the system templates. You can download these templates directly from the **Admin Panel > System Migration** page.

### ðŸ“¥ Downloadable Templates:
- **Member Master:** Full KYC, contact info, and admission details.
- **Balances Master:** Opening totals for all 22+ financial schemes.
- **Loans Master:** Outstanding loan balances, intervals, and repayment counts.
- **Passbook Master:** Historical monthly contributions (January to December).

---

## 3. The Migration Workflow (Step-by-Step)

Follow this exact order to maintain data integrity:

### Step 1: Clean Sweep (Optional)
If you have demo data in the system, click the **"Clean Sweep Demo Data"** button. 
- **What it does:** Deletes non-migration contributions, wallet transactions, and loans.
- **Result:** All user balances are reset to zero, providing a clean slate.

### Step 2: Member Master Import
Import the core member profiles first.
- **Strategy:** Uses `updateOrCreate`. If a member exists, their info is updated.
- **Key Columns:** `membership_no`, `name`, `surname`, `phone`, `branch`, `gender`, `dob`, `nok_name`, etc.
- **Note:** The `phone` number will be the member's initial password.

### Step 3: Balances Master Import
Set the current "Opening Balance" for every member across all schemes.
- **Strategy:** Reconciliation. It calculates the difference between the Excel value and the current DB value, then applies a "Migration Adjustment."
- **Covers:** Savings, Shares, Takaful, Building, Emergency, Digital Gold (grams), etc.

### Step 4: Loans Master Import
Initialize active loans and repayment schedules.
- **New Features:** Now supports `interval` (Daily/Weekly/Monthly) and `total_installments`.
- **Cleanup:** Automatically removes existing demo loans for the member before importing the real one.

### Step 5: Passbook Master Import (History Reconciliation)
Backfill the historical monthly data for a specific year and scheme.
- **Strategy:** Delete-and-Reset for the specific Year/Scheme.
- **Workflow:** 
    1. Wipes all existing contributions for that member/scheme/year.
    2. Inserts monthly values from Jan-Dec.
    3. Triggers a **Sync** to update the member's total balance.

### Step 6: Generate Reconciliation Report
After all imports are finished, click the **"Run Reconciliation"** button.
- **What it does:** Calculates the grand total of all migrated liabilities (Savings, Shares, Takaful, etc.) across the entire database.
- **Purpose:** Compares the system's final state against your manual balance sheet to ensure 100% matching.

---

## 4. Column Definitions & Formats

### Member Master (Partial List)
| Column Name | Required | Description |
| :--- | :--- | :--- |
| `membership_no` | Yes | Unique ID (e.g., 001) |
| `name` | Yes | First Name |
| `surname` | No | Last Name |
| `phone` | Yes | Primary Contact (Username) |
| `branch` | Yes | Branch Name (e.g., Lagos) |
| `dob` | No | YYYY-MM-DD |
| `admission_date` | No | YYYY-MM-DD |

### Balances Master (22+ Schemes)
All columns should be numeric. Use `0` if empty.
- `wallet_balance`
- `ordinary_savings`
- `shares_capital`
- `takaful_balance`
- `building_balance`
- `gold_balance` (Grams, e.g., 1.5)
- ... (Refer to the template for full list)

### Passbook Master
- `membership_no` (Required)
- `scheme_name` (e.g., Savings, Takaful, Shares)
- `year` (e.g., 2026)
- `january`, `february` ... `december` (Amount values)

---

## 5. Safety & Idempotency

- **Safety:** Every import uses database transactions. If an error occurs in a row, the entire batch for that row is rolled back.
- **Re-runs:** You can re-upload the same file multiple times. The system is designed to update existing records or reset history to match the file exactly, preventing "Double Incrementing."
- **Audit Trail:** Every migration action is logged with a reference starting with `MIG-`. You can track these in the **Wallet Transactions** log.

---

## 6. Troubleshooting

- **Error: "Membership Number Not Found":** Ensure you have successfully imported the **Member Master** before trying to import balances or loans for that ID.
- **Error: "Branch Not Found":** Ensure the branch name in Excel matches the name of a branch in the system exactly (Case-sensitive).
- **Date Errors:** Use `YYYY-MM-DD` format for all dates in Excel.

---

**Last Updated:** 2026-04-14
**Version:** 2.0 (Clean Sweep Edition)
