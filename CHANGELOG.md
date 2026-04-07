Changelog

All notable changes to this project will be documented in this file.

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
- Merchant QR payments ("Pay with Attaqwa") documented and integrated front and back.
  - Backend
    - Added MerchantPayController with endpoints:
      - GET /api/merchant/pay/qr – generate a merchant QR payload (attaqwa:pay?...)
      - POST /api/merchant/pay/resolve – resolve scanned QR to merchant details (handles branch disambiguation)
      - POST /api/merchant/pay – execute payment (delegates to /api/wallet/transfer for PIN, balance checks, ledger entries)
    - Routes wired under authenticated group in backend/routes/api.php.
    - Docs: MERCHANT_API.md
  - Frontend (Vue + Capacitor)
    - New views: MerchantReceive.vue and MerchantPay.vue
    - Camera-based QR scanning via @capacitor-mlkit/barcode-scanning
    - Router paths: /merchant/receive and /merchant/pay
    - iOS Info.plist includes NSCameraUsageDescription
    - Docs: FRONTEND_MERCHANT_QR.md
- Documentation index added: DOCS.md linking major guides and references.
