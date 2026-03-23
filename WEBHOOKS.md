# Webhooks Overview

This repository supports multiple inbound webhooks used to reconcile payments and value‑added services. This document provides a quick overview and links to detailed guides.


## Paystack (Payments)
- Route: POST /api/webhooks/paystack
- Auth: Public; verification performed in WebhookController::handlePaystack
- Purpose: Receive payment events (e.g., charge.success) and update internal records.
- Notes: Ensure your Paystack dashboard points to your public URL + this path.


## VTpass (VTU Airtime & Data)
- Route: POST /api/vtu/webhook
- Auth: Public; payload validated and reconciled in UtilityController::handleWebhook
- Purpose: Finalize airtime/data transactions asynchronously and adjust member wallets idempotently.
- Guide: See VTPASS_WEBHOOK.md for complete setup, payload samples, testing, and troubleshooting.


## Local Development: Exposing a Public URL
If you run the backend via Laravel Sail locally, you need a public URL so providers can reach your webhooks.

- Sail Share (recommended):
  ```bash
  ./vendor/bin/sail share
  ```
  Keep the terminal open. The printed HTTPS URL is temporary; close the window and the URL stops working.

- Ngrok (alternative):
  ```bash
  ngrok http 80
  ```

After you have a public URL, configure your provider dashboards accordingly:
- Paystack: https://YOUR-PUBLIC-URL/api/webhooks/paystack
- VTpass:   https://YOUR-PUBLIC-URL/api/vtu/webhook


## Security Notes
- These endpoints are intentionally unauthenticated so providers can call them. Implement perimeter protections where possible (e.g., IP allow‑listing, WAF rules).
- Each controller performs verification/validation tailored to the provider.
- All webhook payloads are logged for diagnostics; ensure logs are shipped/rotated securely.


## Troubleshooting
- Provider dashboard says webhook not reachable:
  - Confirm your tunnel (Sail Share/Ngrok) is running and URL is correct/HTTPS.
- Seeing duplicates:
  - Webhook processing is idempotent; duplicates are safe, but investigate provider retries in logs.
- Environment not configured:
  - Check backend/.env for provider keys and ensure backend/config/services.php has correct values.


— Last updated: 2026-03-20
