# Admin Panel: Bank-Grade Security & Auditing

The cooperative's management system implements multiple "bank-grade" security and auditing layers to ensure financial integrity, prevent internal fraud, and maintain compliance with Shariah principles.

## 1. Multi-Factor Authentication (MFA/2FA)

To protect administrative accounts from password theft, **Two-Factor Authentication (2FA)** is integrated into the Filament Admin Panel via the `filament-breezy` package.

### Key Features:
- **TOTP-based 2FA**: Admins must use a mobile authenticator app (like Google Authenticator or Microsoft Authenticator) to provide a one-time code during login.
- **Recovery Codes**: Users are provided with recovery codes to regain access if they lose their mobile device.
- **Session Management**: Admins can view and manage their active browser sessions, with the ability to remotely log out from other devices.

### How to Enable:
Admins can enable 2FA from their profile page within the Filament panel. Once enabled, it becomes a mandatory second step for every subsequent login attempt.

---

## 2. Browser Session Monitoring

Security is enhanced by tracking all active login sessions for administrative users.

### Features:
- **Persistent Sessions**: All active sessions are stored in the `breezy_sessions` database table.
- **Session Details**: Includes device type, browser, IP address, and last activity timestamp.
- **Remote Logout**: Admins can terminate suspicious or forgotten sessions directly from the "My Profile" page.

---

## 3. Full Audit Trail (Activity Logging)

Every administrative action that modifies critical data is automatically tracked using `spatie/laravel-activitylog`. This ensures absolute accountability within the cooperative.

### Tracked Models:
Audit logging is active for the following core entities:
- **User**: KYC updates, membership status, and balance adjustments.
- **QardHasan (Loans)**: Approvals, rejections, disbursements, and repayments.
- **Contributions**: Member share and savings deposits.
- **WithdrawalRequest**: Approval and payment processing.
- **StoreOrder (Murabaha)**: Financing approvals and order state changes.
- **WalletTransaction**: All manual wallet credits/debits.
- **TakafulContribution**: Pool entries and status updates.
- **ProjectInvestment & Profit**: Funding and dividend distributions.

### Activity Log Interface:
Admins can access the **Activity Log** resource under the "Security & Logs" group. It provides:
- **Causer**: The specific admin who performed the action.
- **Subject**: The record that was modified.
- **Changes**: A side-by-side comparison of "Before" and "After" values for every changed attribute.
- **Read-Only**: Logs are strictly read-only and cannot be modified or deleted, preserving the integrity of the audit trail.

---

## 4. Shariah Audit Logs

Beyond standard activity tracking, a dedicated **Shariah Audit Log** system is implemented for sensitive financial and religious compliance events.

### Purpose:
Ensures that all actions affecting the cooperative's Shariah-compliant status (like charity distributions, Zakat calculations, and profit-sharing ratios) are specifically flagged for review by the Shariah Board or auditors.

### Features:
- **Action Tags**: Logs are tagged with specific actions (e.g., `APPROVE_MURABAHA`, `DISTRIBUTE_PROFIT`, `VERIFY_KYC`).
- **Contextual Metadata**: Stores JSON payloads of the state change for detailed forensic review.
- **Integration**: Directly hooked into critical controllers and Filament resource actions.

---

## 5. Member 360° Financial View

To improve oversight, the `UserResource` (Member view) includes comprehensive **Relation Managers** that allow admins to see a member's entire history in one place:
- **Wallet History**: Every transaction ever made by the member.
- **Contributions**: Detailed record of all scheme-based deposits.
- **Loans (Qard Hasan)**: Full loan portfolio and repayment history.
- **Investments**: Participation in Mudarabah projects and profits received.
- **Takaful**: Protection pool participation and claims.
- **Activity Logs**: See exactly which admin modified this member's profile and when.

---

## 6. Manual Controls & Printing
To ensure financial oversight, the admin panel provides controlled manual actions with mandatory auditing:
- **Wallet Adjustments**: Admins can manually credit or debit member wallets, with each adjustment requiring a note and being logged in both Activity and Shariah Audit logs.
- **2FA Resets**: If a member loses access to their authenticator device, an authorized admin can reset their 2FA, with the action strictly audited.
- **Member Application Onboarding**: Full workflow for approving or rejecting new member applications, including automatic user creation and auditing.
- **Financial Printing**: Generating secure, read-only PDF receipts and passbooks for members, ensuring that physical or digital proof of transactions is always available.

---

## Security Best Practices for Admins
1. **Always use 2FA**: Mandatory for all accounts with financial access.
2. **Review Sessions Weekly**: Logout from public or shared devices immediately.
3. **Monitor Activity Logs**: Regularly audit large manual adjustments or bulk updates.
4. **Shariah Compliance**: Ensure all profit distributions are logged via the Shariah Audit resource.
