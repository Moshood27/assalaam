Documentation Index

Date: 2026-04-04

Start here
- README.md – High‑level overview, architecture notes, and setup guidance.
- BUILD_AND_DEPLOY.md – Build, run, and deploy instructions.
- DEPLOYMENT.md – Server environment and deployment checklist.
- VPS_UPGRADE_GUIDE.md – **NEW:** Steps to update the existing VPS with new implementation features.
- MOBILE_BUILD_SYNC.md – Capacitor mobile projects: syncing and assets.
- MOBILE_VERSIONING.md – Managing app versions and forced updates.
- PLAYSTORE_DEPLOYMENT.md – Google Play Store publishing guide.

Feature guides
- STORE_ECOMMERCE.md – Coop Store & E-Commerce (member catalog, cart, Murabaha financing, and admin order management).
- MERCHANT_API.md – Backend API for “Pay with Attaqwa” Merchant QR payments (QR payload format, resolve and pay endpoints).
- FRONTEND_MERCHANT_QR.md – Frontend/mobile guide for Merchant QR (camera scanner, flows, routes, permissions).
- TAKAFUL.md – Mutual protection pool (member and admin flows).
- MUDARABAH.md – Pooled investment projects (models, flows, profit booking and distribution).
- QARD_HASAN.md – Benevolent loan logic, flows, and automated recovery hunters.
- KYC_SYSTEM.md – KYC/Identity verification via BVN and face matching (Dojah/Mock).
- USER_NOTIFICATIONS.md – In‑app notifications and push integration.
- PUSH_NOTIFICATIONS.md – Mobile push notifications and FCM.
- AGM_VOTING.md – AGM sessions, candidates, voting and results.
- BRANCH_PERFORMANCE_ANALYTICS.md – Branch network visual map and key performance indicators (savings, delinquency).
- BRANCH_MANAGEMENT_OPERATIONS.md – Branch administration, member assignment, and bulk communication.
- ADMIN_SECURITY_AUDIT.md – Admin panel bank-grade security and full auditing.
- VIRTUAL_ACCOUNT.md – Dedicated virtual accounts (Paystack DVA) for wallet top‑ups.
- WEBHOOKS.md – General webhook handling patterns.
- VTPASS_WEBHOOK.md – VTU provider webhook specifics.
- TELESCOPE_HORIZON.md – Monitoring jobs, webhooks, and debugging.

APIs and backend
- backend/routes/api.php – Authenticated and public API routes map.
- WalletController (backend/app/Http/Controllers/Api/WalletController.php) – Wallet endpoints (get balance, allocate, transfer, withdraw).
- MerchantPayController (backend/app/Http/Controllers/Api/MerchantPayController.php) – Merchant QR endpoints.

Changelog
- See CHANGELOG.md for dated highlights of notable changes.

Contributing
- Keep new documentation in the project root as .md files.
- When adding a feature that impacts users or developers, create or update a dedicated .md file and add a link here.
