# Attendance & Apology Fine System

This document explains how the attendance and apology (lateness) fine system works in Attaqwa Pay and outlines planned improvements.

## 1. How Fines are Charged

The system automatically identifies and charges two types of fines related to meeting attendance:

### A. Apology (Lateness) Fine
- **Trigger**: When a member marks attendance after the meeting's scheduled start time plus any **Grace Period** set by the admin.
- **Grace Period**: Admins can set a grace period (e.g., 15 minutes) for each meeting. If the meeting starts at 10:00 AM and the grace period is 15 minutes, the lateness fine is only triggered after 10:15 AM.
- **Amount**: Determined by the `apology_fine_amount` set on the specific `Meeting` record. If not set, it defaults to the system-wide `COOP_APOLOGY_FINE` (usually ₦100).
- **Process**:
    - If the member has sufficient wallet balance, the fine is deducted immediately.
    - If the balance is insufficient, the amount is added to the member's `outstanding_fines` balance.
    - A record is created in the **Charity Ledger (Sadaqah Fund)**.

### B. Absence Fine
- **Trigger**: When the "Audit Attendance" script runs for a completed meeting.
- **Amount**: Determined by the `fine_amount` set on the `Meeting` record. If not set, it defaults to the system-wide `COOP_ATTENDANCE_FINE` (usually ₦500).
- **Process**:
    - Members who are not marked as "Present" or "Apology Paid" are charged.
    - Similar to lateness fines, it is either deducted from the balance or added to `outstanding_fines`.
    - A record is created in the **Charity Ledger**.

---

## 2. How Fines are Paid

There are three primary ways fines are settled in the system:

### A. Immediate Deduction
If a member has enough money in their wallet at the time the fine is issued, the system debits the wallet instantly. This is the most seamless method.

### B. Automatic Debt Recovery (Auto-Settlement)
This is the core "Fintech" feature of the system. 
- **Trigger**: Whenever a member's wallet balance increases (e.g., via Top-Up, Transfer Receipt, or Bank Deposit).
- **Action**: An observer (`UserObserver`) detects the balance increase and automatically checks if the member has any `outstanding_fines`. 
- **Settlement**: It automatically deducts the accumulated fine amount from the new balance, updates the `outstanding_fines` total, and marks the relevant attendance records as paid.

### C. Manual Payment
Members can use the **"Make Payment"** screen in the mobile app:
- Select any scheme (e.g., Savings).
- Enter the fine amount.
- Check the **"Lateness/Apology Fine"** box.
- *Note: In the current version, this records the payment as a contribution. We are improving this to directly settle outstanding fines.*

### D. Admin Manual Actions
Admins can manually manage member fines from the Filament Admin Panel (`UserResource`):
- **Record Fine Payment**: If a member pays their fine in cash or via external transfer, an admin can record this payment. This creates a successful contribution record and automatically reduces the member's `outstanding_fines`.
- **Waive All Fines**: In special cases (e.g., system errors or approved waivers), admins can clear all outstanding fines for a member with a single click. This sets the `outstanding_fines` balance to ₦0 and marks associated attendance records as paid/settled.
- **Bulk Waivers**: Admins can select multiple members in the "Members" list and use the "Waive Fines" bulk action to clear debts for all selected users.

---

## 3. Better Improvements

To make the fine system more transparent and user-friendly, the following improvements are being implemented:

### 1. Dedicated "Pay Outstanding Fines" Button
Instead of checking a box in the contribution screen, members with outstanding fines will see a prominent "Clear Fines" button on their Dashboard. This will take them to a dedicated payment flow that specifically targets their debt.

### 2. Direct Fine Allocation in Backend
The backend `allocateToSchemes` and `initiatePayment` endpoints are being updated to recognize the `category: 'fine'` flag. When this flag is present:
- The system will prioritize clearing `outstanding_fines` instead of creating a standard scheme contribution.
- The funds will be moved directly to the Charity Ledger.

### 3. Improved Fine Notifications
Members currently receive a notification when a fine is charged. We will add a "Reminder" notification if they have outstanding fines for more than 7 days, encouraging them to top up their wallet for auto-settlement.

### 4. Visibility of Fine History
A new "Fine History" tab in the Attendance section will show exactly which meetings resulted in fines, which were paid immediately, and which were recovered later via auto-settlement.
