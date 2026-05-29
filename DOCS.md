Documentation Index

Date: 2026-04-04

Start here
- README.md â€“ Highâ€‘level overview, architecture notes, and setup guidance.
- MIGRATION_GUIDE.md â€“ **NEW:** Comprehensive step-by-step guide for the "Delete and Reset" system migration.
- BUILD_AND_DEPLOY.md â€“ Build, run, and deploy instructions.
- DEPLOYMENT.md â€“ Server environment and deployment checklist.
- VPS_UPGRADE_GUIDE.md â€“ **NEW:** Steps to update the existing VPS with new implementation features.
- MOBILE_BUILD_SYNC.md â€“ Capacitor mobile projects: syncing and assets.
- MOBILE_VERSIONING.md â€“ Managing app versions and forced updates.
- PLAYSTORE_DEPLOYMENT.md â€“ Google Play Store publishing guide.

Feature guides
- STORE_ECOMMERCE.md â€“ Coop Store & E-Commerce (member catalog, cart, Murabaha financing, and admin order management).
- MERCHANT_API.md â€“ Backend API for â€œPay with assalaamâ€ Merchant QR payments (QR payload format, resolve and pay endpoints).
- FRONTEND_MERCHANT_QR.md â€“ Frontend/mobile guide for Merchant QR (camera scanner, flows, routes, permissions).
- TAKAFUL.md â€“ Mutual protection pool (member and admin flows).
- MUDARABAH.md â€“ Pooled investment projects (models, flows, profit booking and distribution).
- QARD_HASAN.md â€“ Benevolent loan logic, flows, and automated recovery hunters.
- LOAN_PENALTY_SYSTEM.md â€“ **NEW:** Automated default-based loan penalty wait period enforcement.
- KYC_SYSTEM.md â€“ KYC/Identity verification via BVN and face matching (Dojah/Mock).
- USER_NOTIFICATIONS.md â€“ Inâ€‘app notifications and push integration.
- ADMIN_CHAT_GUIDE.md â€“ Real-time admin-member support chat guide.
- PUSH_NOTIFICATIONS.md â€“ Mobile push notifications and FCM.
- AGM_VOTING.md â€“ AGM sessions, candidates, voting and results.
- BRANCH_PERFORMANCE_ANALYTICS.md â€“ Branch network visual map and key performance indicators (savings, delinquency).
- BRANCH_MANAGEMENT_OPERATIONS.md â€“ Branch administration, member assignment, and bulk communication.
- ADMIN_SECURITY_AUDIT.md â€“ Admin panel bank-grade security and full auditing.
- VIRTUAL_ACCOUNT.md â€“ Dedicated virtual accounts (Paystack DVA) for wallet topâ€‘ups.
- WEBHOOKS.md â€“ General webhook handling patterns.
- VTPASS_WEBHOOK.md â€“ VTU provider webhook specifics.
- TELESCOPE_HORIZON.md â€“ Monitoring jobs, webhooks, and debugging.

APIs and backend
- backend/routes/api.php â€“ Authenticated and public API routes map.
- WalletController (backend/app/Http/Controllers/Api/WalletController.php) â€“ Wallet endpoints (get balance, allocate, transfer, withdraw).
- MerchantPayController (backend/app/Http/Controllers/Api/MerchantPayController.php) â€“ Merchant QR endpoints.

Changelog
- See CHANGELOG.md for dated highlights of notable changes.

Contributing
- Keep new documentation in the project root as .md files.
- When adding a feature that impacts users or developers, create or update a dedicated .md file and add a link here.
