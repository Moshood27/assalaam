# System Migration Strategy

> **Note:** For detailed step-by-step instructions on how to use the migration tools in the Admin Panel, please refer to the **[MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)**.

## 1) Concept Overview
- The "Opening Balance" migration strategy is designed to transition Attaqwa Cooperative from paper/Excel records to the digital platform without importing every historical transaction.
- **Amanah (Trust):** Maintaining accuracy is paramount. The system creates a clear audit trail for every migrated balance so members can verify their starting point.
- **Cut-off Date:** A specific date (e.g., Dec 31st) where paper books are closed and digital records begin.

## 2) The "Delete and Reset" Approach (v2.0)
- In addition to standard imports, we've implemented a **"Delete and Reset"** strategy to handle transition from testing/demo phases to a Live environment.
- This ensures any demo funds created by members are wiped before the official migration data is loaded.
- History is rebuilt from monthly Excel "Passbook Master" files to ensure a clean history view for members.

---

## 3) Phase 1: Data Cleaning (Excel)
Excel sheets must be formatted exactly to match the import logic.

### Member Master
- **Fields:** `membership_no`, `name`, `phone`, `email`, `branch`, `address`.
- **Constraint:** `membership_no` must be unique. `branch` should match a Branch name (e.g., Lagos).

### Balances Master
- **Fields:** `membership_no`, `savings_balance`, `shares_balance`, `takaful_balance`, `development_fund_balance`, `wallet_balance`, `building_balance`, `agm_balance`, `loan_repayment_balance`, `fine_balance`, `welfare_balance`, `lateness_balance`, `stationery_balance`, `loan_form_balance`, `others_balance`, `id_card_balance`, `emergency_balance`, `entrance_balance`, `h_savings_balance`, `investment_balance`, `digital_gold_balance`, `group_savings_balance`, `outstanding_fines`.
- **Constraint:** All values must be numeric. `digital_gold_balance` is the weight in grams (e.g., 0.50).

### Loan Master
- **Fields:** `membership_no`, `original_loan_amount`, `total_repaid_to_date`, `remaining_principal`, `next_installment_amount`, `interval`, `total_installments`, `received_at`, `defaulted_at`.
- **Constraint:** All values must be numeric except `membership_no`, `interval`, and date fields. `received_at` and `defaulted_at` should be in YYYY-MM-DD format.

---

## 4) Phase 2: Administrative Import (Filament)
The migration tool is located in the Admin Panel under **System Migration**.

1. **Set Migration Date:** Choose the cut-off date. This date will be applied to all created transactions.
2. **Sequential Upload:**
   - **Step 1: Members.** Create the user accounts first.
   - **Step 2: Balances.** Populate the financial buckets.
   - **Step 3: Loans.** Initialize active repayment schedules.
3. **Validation:** The system will skip rows with invalid data and provide a detailed error log if the import fails.

---

## 5) Phase 3: Reconciliation & Certification
Before notifying members, the Admin must perform a final audit.

1. **Reconciliation Summary:** The Migration page displays the grand total of all migrated funds (e.g., "Total Migrated Savings: ₦5,200,000").
2. **PDF Audit Report:** Generate the **Migration Points of Truth** PDF. This document contains:
   - Total members migrated.
   - Sum of all opening balances per scheme.
   - Signature lines for the **Treasurer**, **Chairman**, and **Sharia Auditor**.
3. **Locking:** Once certified, the Excel records are considered "Legacy" and the Digital App becomes the single source of truth.

---

## 6) Phase 4: Member Verification (Frontend)
Transparency helps build trust with the cooperative members.

1. **Onboarding SMS:** Admins can trigger a "Send Onboarding SMS" to all migrated members with their login details.
2. **Verification Prompt:** On first login, members see a non-dismissible modal:
   - "Your migrated opening balance is **₦XX,XXX.XX** (Detailed breakdown of all 20+ schemes including Savings, Shares, Takaful, and other funds)."
3. **Actions:**
   - **Verify:** Member confirms accuracy. `verified_at` is set.
   - **Report Discrepancy:** Member submits a brief explanation of the error. A support message is generated for Admin review.

---

## 7) Handling Discrepancies
- **Review:** Admins check the "Discrepancy Reports" in the Support section.
- **Adjustment:** If an error is found, the Admin uses the **Adjustments** tool to credit/debit the member's account.
- **Manual Verification:** After resolution, the Admin can manually mark the member as `verified`.

---

## 8) Deployment to VPS
To deploy these changes to your live server, please follow the [VPS_UPGRADE_GUIDE.md](VPS_UPGRADE_GUIDE.md).
