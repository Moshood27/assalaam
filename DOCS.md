Documentation Index

Date: 2026-04-04

Start here
- README.md – High‑level overview, architecture notes, and setup guidance.
- BUILD_AND_DEPLOY.md – Build, run, and deploy instructions.
- DEPLOYMENT.md – Server environment and deployment checklist.
- MOBILE_BUILD_SYNC.md – Capacitor mobile projects: syncing and assets.

Feature guides
- MERCHANT_API.md – Backend API for “Pay with Attaqwa” Merchant QR payments (QR payload format, resolve and pay endpoints).
- FRONTEND_MERCHANT_QR.md – Frontend/mobile guide for Merchant QR (camera scanner, flows, routes, permissions).
- TAKAFUL.md – Mutual protection pool (member and admin flows).
- MUDARABAH.md – Pooled investment projects (models, flows, profit booking and distribution).
- QARD_HASAN.md – Benevolent loan logic and flows.
- USER_NOTIFICATIONS.md – In‑app notifications and push integration.
- PUSH_NOTIFICATIONS.md – Mobile push notifications and FCM.
- AGM_VOTING.md – AGM sessions, candidates, voting and results.
- VIRTUAL_ACCOUNT.md – Dedicated virtual accounts (Paystack DVA) for wallet top‑ups.
- WEBHOOKS.md – General webhook handling patterns.
- VTPASS_WEBHOOK.md – VTU provider webhook specifics.

APIs and backend
- backend/routes/api.php – Authenticated and public API routes map.
- WalletController (backend/app/Http/Controllers/Api/WalletController.php) – Wallet endpoints (get balance, allocate, transfer, withdraw).
- MerchantPayController (backend/app/Http/Controllers/Api/MerchantPayController.php) – Merchant QR endpoints.

Changelog
- See CHANGELOG.md for dated highlights of notable changes.

Contributing
- Keep new documentation in the project root as .md files.
- When adding a feature that impacts users or developers, create or update a dedicated .md file and add a link here.
