Changelog

All notable changes to this project will be documented in this file.

2026-05-12
- Enhanced Flutterwave Dedicated Virtual Account (DVA) integration:
  - Added support for regenerating virtual accounts for members via `POST /api/virtual-account/regenerate-flutterwave`.
  - Implemented mandatory BVN validation for members before DVA creation, ensuring profile names match BVN identity.
  - Fixed an issue where DVA payments were not crediting wallets due to missing user identification fallbacks in the webhook.
  - Improved webhook idempotency for DVA payments by using `flw_ref` as the unique reference.
  - Documentation: VIRTUAL_ACCOUNT.md, KYC_SYSTEM.md

2026-04-28
- Implemented automated Loan Penalty System for defaulted members.
  - Automatically triggers a penalty record when a member defaults on a loan.
  - Calculates a mandatory wait period for new loans exactly equal to the duration spent in default.
  - Enforces wait periods via API during eligibility checks and loan application submission.
  - Added a "Loan Penalties" admin report with branch filtering and precise duration formatting.
  - Documentation: LOAN_PENALTY_SYSTEM.md

2026-04-16
- Implemented "Wipe Module" feature for all Admin Filament resources.
  - Added `HasWipeAction` trait to provide a consistent "Wipe Module" header action.
  - Integrated "Wipe Module" button across all 38 resources (Activity Logs, Users, Branches, etc.) using a robust chunked deletion process to honor model events and handle large datasets.
  - Restricted "Wipe Module" action to `super_admin` role only.
  - Included a safety check to prevent the current `super_admin` from deleting their own account in the User module.
  - Added confirmation modals to prevent accidental data loss.

2026-04-07
- Implemented "Bank-Grade" Security and Auditing for the Admin Panel.
  - Added Multi-Factor Authentication (MFA/2FA) and Browser Session management via `filament-breezy`.
  - Implemented full Activity Logging (Audit Trail) using `spatie/laravel-activitylog` across all critical financial models.
  - Created reusable `ActivitiesRelationManager` for record-level audit visibility in User, Loan, and Contribution resources.
  - Enhanced `UserResource` with 360-degree financial relation managers (Wallet, Contributions, Loans, Investments, Takaful).
  - Integrated Shariah Audit logging for sensitive compliance actions (profit distribution, KYC verification, charity entries).
  - Added new administrative resources: `MemberApplicationResource`, `CharityEntryResource`, and `SupportMessageResource`.
  - Global financial monitoring via `WalletTransactionResource` and `UtilityTransactionResource`.
  - Documentation: ADMIN_SECURITY_AUDIT.md

2026-04-06
- Added and configured Laravel Telescope and Horizon for production monitoring.
  - Dashboards available at /app/telescope and /app/horizon.
  - Configured webhook tagging and sensitive header filtering.
- Improved documentation for core features:
  - Added TELESCOPE_HORIZON.md (monitoring and debugging).
  - Added KYC_SYSTEM.md (identity verification logic).
  - Updated QARD_HASAN.md with Auto Recovery (loan hunter) details.
- Updated composer.json to auto-publish monitoring assets on update.

2026-04-04
- Merchant QR payments ("Pay with assalaam") documented and integrated front and back.
  - Backend
    - Added MerchantPayController with endpoints:
      - GET /api/merchant/pay/qr â€“ generate a merchant QR payload (assalaam:pay?...)
      - POST /api/merchant/pay/resolve â€“ resolve scanned QR to merchant details (handles branch disambiguation)
      - POST /api/merchant/pay â€“ execute payment (delegates to /api/wallet/transfer for PIN, balance checks, ledger entries)
    - Routes wired under authenticated group in backend/routes/api.php.
    - Docs: MERCHANT_API.md
  - Frontend (Vue + Capacitor)
    - New views: MerchantReceive.vue and MerchantPay.vue
    - Camera-based QR scanning via @capacitor-mlkit/barcode-scanning
    - Router paths: /merchant/receive and /merchant/pay
    - iOS Info.plist includes NSCameraUsageDescription
    - Docs: FRONTEND_MERCHANT_QR.md
- Documentation index added: DOCS.md linking major guides and references.
