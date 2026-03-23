# VTpass Webhook Guide (Airtime & Data)

This document explains how to expose, configure, and test the VTpass webhook used by the Cooperative app to finalize VTU (airtime/data) transactions.

The backend already implements a VTpass-compatible webhook handler.

- Public endpoint: POST /api/vtu/webhook
- Purpose: Receive asynchronous status updates from VTpass and reconcile member wallets.
- Idempotency: Safe to receive duplicate callbacks; wallet will not be double‑debited or double‑refunded.


## 1) Expose a Public URL (Local Development)
VTpass must reach your server from the internet. When developing locally with Laravel Sail, use Sail Share (recommended) or Ngrok.

- Sail Share (recommended):
  ```bash
  ./vendor/bin/sail share
  ```
  Keep this terminal open. It will print a public HTTPS URL like:
  https://YOUR-APP.laravel-sail.site

- Ngrok (alternative):
  ```bash
  ngrok http 80
  ```
  or point to your Sail port if not on 80.


## 2) Configure VTpass Dashboard
1. Log in to VTpass (sandbox or live).
2. Go to Setup Webhook.
3. Set your webhook URL to:
   https://YOUR-PUBLIC-URL/api/vtu/webhook
4. Save changes.

Notes:
- If you close the Sail Share/Ngrok terminal, the URL stops working.
- Use the HTTPS variant that VTpass provides without warnings.


## 3) What the Webhook Does
When VTpass calls POST /api/vtu/webhook, the backend:
- Logs the entire payload for diagnostics.
- Extracts the transaction reference from typical fields: request_id, requestId, reference, data.requestId, or content.transactions.requestId.
- Finds the matching UtilityTransaction by reference.
- Determines final status using common VTpass response patterns:
  - success when status == "success", code == "000", nested transactions.status == "successful", or response_description contains "success/ completed".
  - pending when any of the above indicate pending/processing.
  - failed otherwise.
- Reconciles the member wallet idempotently:
  - success: set tx=success, compute profit, and ensure a single wallet debit exists.
  - failed: set tx=failed and issue a single refund credit if a prior debit exists.
  - pending: keep tx=pending, no balance changes.
- Always stores provider_response (the full payload) on the UtilityTransaction for audit.


## 4) Reference (request_id) Format Requirement
VTpass is strict about request_id. The backend generates a compliant default when you don’t override the reference:
- Format: UTC timestamp (YYYYMMDDHHmm) followed by a unique 6‑char string.
- Example: 202603201415a1b2c3

If you supply your own reference, ensure it starts with the current UTC datetime in the same format. Avoid prefixes like "VTU-AIRTIME-" as VTpass may reject them.


## 5) Endpoint Details
- Method: POST
- Path: /api/vtu/webhook
- Auth: Public (VTpass servers). Consider IP allow‑listing at your perimeter if possible.
- Content type: application/json (preferred). Form-encoded is tolerated as long as fields are present.
- Response: 200 { "status": "received" }

If the reference is missing or unknown, the endpoint still responds 200 but logs a note. This prevents VTpass from retrying indefinitely while keeping your logs informative.


## 6) Example Payloads
Success (typical):
```json
{
  "status": "success",
  "code": "000",
  "response_description": "TRANSACTION SUCCESSFUL",
  "request_id": "202603201415a1b2c3",
  "content": {
    "transactions": {
      "status": "successful",
      "transactionId": "1234567890",
      "amount": 1000,
      "phone": "08031234567",
      "serviceID": "mtn"
    }
  }
}
```

Pending:
```json
{
  "status": "pending",
  "response_description": "Transaction is processing",
  "requestId": "202603201418d4e5f6"
}
```

Failed:
```json
{
  "status": "failed",
  "response_description": "Insufficient wallet balance at provider",
  "reference": "202603201420aa11bb"
}
```


## 7) Wallet Impact Summary
- Airtime success: Debit exactly amount recorded on the UtilityTransaction.
- Data success: Debit base amount + configured convenience fee (already stored as tx amount); meta includes convenience_fee and bundle_code.
- Failed after prior debit: Create a REFUND credit (reference: {original}-REFUND) and restore balance.

All operations are wrapped in a DB transaction with row‑level locking to ensure atomicity and idempotency.


## 8) Configuration & Environment
VTpass credentials and behavior are configured in backend/config/services.php under the "vtu" section.

Environment variables (backend/.env):
- VTPASS_API_KEY=
- VTPASS_PUBLIC_KEY=
- VTPASS_SECRET_KEY=
- VTU_SANDBOX=true            # true in local/dev by default
- VTU_DEFAULT_DISCOUNT=0.03   # revenue knob applied as cost discount
- VTU_CONVENIENCE_FEE=0       # flat fee added on data purchases
- VTU_WEBHOOK_URL=            # set to your public URL + /api/vtu/webhook (e.g., from Sail Share/Ngrok). Defaults to ${APP_URL}/api/vtu/webhook

The base URL auto-selects sandbox (https://sandbox.vtpass.com/api) when VTU_SANDBOX is true or when APP_ENV=local, unless overridden.


## 9) Testing the Webhook Manually
- With curl (simulate success):
  ```bash
  curl -X POST \
       -H "Content-Type: application/json" \
       -d '{
             "status":"success",
             "code":"000",
             "request_id":"202603201415a1b2c3",
             "content":{"transactions":{"status":"successful"}}
           }' \
       https://YOUR-PUBLIC-URL/api/vtu/webhook
  ```

- With Postman: Create a POST request to your public URL and paste a sample JSON payload.

- Logs: Check backend storage/logs/laravel.log for entries tagged "VTpass Webhook Received" and any warnings.


## 10) Troubleshooting
- VTpass dashboard shows a red banner to set webhook:
  - You must provide a public URL (Sail Share/Ngrok) and configure it.
- Transactions remain pending:
  - VTpass may take time to finalize. The app also performs a single requery during purchase; eventual consistency is achieved via webhook.
- Immediate failures:
  - Verify request_id format and that your VTpass sandbox wallet has funds.
  - Ensure headers include api-key and secret-key for purchases (the app already does this).
- Not receiving callbacks:
  - Keep the Sail Share/Ngrok tunnel open; confirm HTTPS URL.
  - Check firewalls and VTpass webhook logs for delivery attempts.


## 11) Production Notes
- Use a stable, permanent public URL (domain) for production.
- Consider restricting the webhook route by network perimeter rules (IP allowlist) and monitoring logs/metrics for anomalies.


— Last updated: 2026-03-20
