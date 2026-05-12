# Virtual Accounts (Dedicated NUBAN) in the Cooperative App

This document explains how Virtual Accounts (also called Dedicated NUBANs in Nigeria) work end‑to‑end in this project: what they are, how members get one, how wallet top‑ups are credited automatically via webhooks, how funds are allocated to schemes, and how to configure and test the feature.

The implementation supports **two providers**: **Paystack** (primary) and **Flutterwave** (alternative). Members can generate accounts from either or both providers. The code is structured so that additional providers can be added in the future with minimal changes.


## What is a Virtual Account?
- A Virtual Account is a bank account number (e.g., Wema/Providus/Titan) dedicated to a specific member.
- When a member transfers NGN to their Virtual Account, the provider sends a webhook to our backend.
- The backend verifies the notification and credits the member’s in‑app wallet automatically.


## High‑Level Flow
1. Member signs in and opens the Wallet screen.
2. If they don’t yet have a Virtual Account, they can tap “Generate Account.”
3. Backend creates (or reuses) a Paystack Customer for the user, then requests/assigns a Dedicated Virtual Account (DVA).
4. The DVA details (bank name, account number, account name) are stored against the user and shown in the app.
5. Member transfers NGN from any bank to that account.
6. Paystack sends a webhook (event: charge.success) to our server.
7. Backend verifies the signature and transaction, identifies the member, credits their wallet, and records a WalletTransaction for audit.
8. Member can allocate wallet funds to Savings/Shares or other schemes.


## Key Backend Components

- Database
  - users table (added fields via migration 2026_03_15_152500_add_paystack_fields_to_users.php):
    - paystack_customer_code (nullable, unique)
    - dva_account_number (nullable, unique)
    - dva_bank_name (nullable)
    - dva_account_name (nullable)
  - wallet_transactions table (2026_03_15_153000_create_wallet_transactions_table.php):
    - type: credit|debit
    - amount: decimal(15,2)
    - reference: unique string (idempotency key)
    - source: e.g., paystack_dva, paystack_charge, wallet_allocation
    - meta: json

- Models
  - App\Models\User: exposes balance and the Paystack/DVA fields.
  - App\Models\WalletTransaction: records all wallet credits/debits with metadata.

- Controllers
  - Api\VirtualAccountController
    - GET /api/virtual-account → returns the user’s DVA details.
    - POST /api/virtual-account/assign → creates a Paystack customer (if missing) and assigns/fetches a DVA. Persists dva_* fields.
  - Api\WalletController
    - GET /api/wallet → returns wallet balance, virtual_account block, and recent transactions.
    - POST /api/wallet/topup/initiate → initializes a card payment via Paystack; webhook will credit on success.
    - POST /api/wallet/allocate → debits wallet and creates scheme contributions instantly.
  - Api\WebhookController
    - POST /api/webhooks/paystack → verifies x-paystack-signature, then verifies the transaction with Paystack’s /transaction/verify API.
    - If there are no pending “Contribution” records tied to the reference, it treats the event as a wallet top‑up (DVA or card), locates the member, credits wallet, and writes a WalletTransaction with source=paystack_dva for bank transfers.

- Routes (backend/routes/api.php)
  - Public webhook: POST /api/webhooks/paystack
  - Authenticated:
    - GET /api/virtual-account
    - POST /api/virtual-account/assign
    - GET /api/wallet
    - GET /api/wallet/transactions
    - POST /api/wallet/topup/initiate
    - POST /api/wallet/allocate


## Frontend Behavior (Vue)
- Wallet.vue
  - Shows member balance and the Virtual Account section.
  - If no account exists, shows a “Generate Account” button which calls POST /api/virtual-account/assign.
  - Displays bank name, account name, and account number with a copy button.
  - Provides “Fund Wallet (Card)” via /api/wallet/topup/initiate.
- Profile.vue
  - Shows a human‑friendly Virtual Account string (e.g., "9920•••••• - Wema Bank (John Doe)").
- Dashboard.vue
  - Shows “Add Money” shortcut to Wallet and recent activities.


## Configuration
Set the following in backend/.env (these are already present in this repo):
- PAYSTACK_PUBLIC_KEY=pk_test_xxx
- PAYSTACK_SECRET_KEY=sk_test_xxx
- PAYSTACK_WEBHOOK_URL=https://your-domain.tld/api/webhooks/paystack

And ensure backend/config/services.php includes:
- 'paystack' => [ 'public_key' => env('PAYSTACK_PUBLIC_KEY'), 'secret_key' => env('PAYSTACK_SECRET_KEY') ]

For Flutterwave DVA, also set:
- FLW_PUBLIC_KEY=FLWPUBK_TEST-xxx
- FLW_SECRET_KEY=FLWSECK_TEST-xxx
- FLW_SECRET_HASH=your_webhook_secret_hash
- FLW_WEBHOOK_URL=https://your-domain.tld/api/webhooks/flutterwave

Notes:
- Your Paystack business must have Dedicated Virtual Accounts (DVA) enabled by Paystack. Without enablement, the /dedicated_account endpoints will return errors.
- Your Flutterwave account must be approved for virtual account numbers. Set the webhook URL and secret hash in the Flutterwave Dashboard under Settings > Webhooks.
- For local testing, you can use ngrok to expose the webhook endpoint. This repo includes optional NGROK_* envs to help.


## How a Member Gets a Virtual Account
- Client calls: POST /api/virtual-account/assign
- Server steps:
  1. If user.paystack_customer_code is empty, create a Paystack customer with their email/name (and optional phone).
  2. Request DVA assignment via POST https://api.paystack.co/dedicated_account/assign with the customer code.
  3. If assignment fails but a DVA already exists, falls back to GET /dedicated_account?customer=... and uses the first entry.
  4. Persist dva_account_number, dva_account_name, dva_bank_name on the user.
  5. Return the DVA in the response and in GET /api/wallet and GET /api/virtual-account.


## How a Member Gets a Flutterwave Virtual Account
- Client calls: POST /api/virtual-account/assign-flutterwave with { bvn: "12345678901" } (BVN is optional if already verified).
- Server steps:
  1. Validates BVN (if provided) and checks if the member's profile already has a verified BVN.
  2. FlutterwaveDvaService calls POST https://api.flutterwave.com/v3/virtual-account-numbers with email, BVN, name, phone, is_permanent=true.
  3. Persists flw_dva_account_number, flw_dva_account_name, flw_dva_bank_name, flw_dva_bank_code, flw_dva_order_ref, flw_dva_flw_ref on the user.
  4. Returns both Paystack and Flutterwave DVA details via GET /api/virtual-account.
- Note: BVN is mandatory for Flutterwave virtual accounts (Nigerian regulatory requirement). If not provided, it must be already verified on the member's profile.

## Regenerating a Flutterwave Virtual Account
- Client calls: POST /api/virtual-account/regenerate-flutterwave with { bvn: "12345678901" } (BVN is optional if already verified).
- This endpoint forces the creation of a new virtual account even if the member already has one.
- The steps are identical to the assignment flow, but it bypasses the "reuse existing" check.


## Funding the Wallet
There are two supported top‑up channels. Both are finalized by the webhook.

1) Bank Transfer to DVA (Recommended)
- Member makes a transfer in NGN to their dedicated account.
- Paystack triggers charge.success webhook to /api/webhooks/paystack.
- Our WebhookController:
  - Verifies x-paystack-signature using secret key.
  - Calls Paystack /transaction/verify/{reference} for extra safety.
  - If no pending Contribution with that reference exists, attempts wallet top‑up:
    - Finds the user by (in order):
      - customer_code (vd.customer.customer_code),
      - receiver account number (authorization.receiver_bank_account_number or authorization.account_number),
      - metadata.user_id (if provided).
    - Converts amount kobo→naira, ensures NGN, ensures amount > 0.
    - Ensures idempotency: if a WalletTransaction with same reference exists, it’s ignored.
    - Increments user.balance and writes WalletTransaction with:
      - type=credit
      - source=paystack_dva (if channel=bank_transfer) or paystack_charge otherwise
      - meta: channel, customer_code, receiver_account

2) Bank Transfer to Flutterwave DVA (Alternative)
- Member makes a transfer in NGN to their Flutterwave dedicated account.
- Flutterwave sends a webhook to /api/webhooks/flutterwave.
- Our WebhookController:
  - Verifies verif-hash header against FLW_SECRET_HASH.
  - Calls Flutterwave /v3/transactions/{id}/verify for extra safety.
  - If no pending Contribution or loan repayment with that reference exists, attempts wallet top‑up:
    - Finds the user by (in order):
      - meta.user_id,
      - flw_dva_account_number (from bank_transfer_details.account_number),
      - dva_account_number (Paystack fallback),
      - customer email.
    - Ensures NGN and amount > 0.
    - Ensures idempotency via unique reference on WalletTransaction.
    - Increments user.balance and writes WalletTransaction with source=flutterwave_dva.

3) Card Top‑up (Fallback)
- Client calls POST /api/wallet/topup/initiate with amount and optional callback_url.
- Server initializes Paystack checkout and returns authorization_url; user completes on Paystack.
- The same webhook path above will confirm and credit wallet on success (source=paystack_charge).


## Allocating Wallet Funds to Schemes
- Client calls POST /api/wallet/allocate with items: [{ scheme_id, amount }, ...].
- Server ensures sufficient balance, wraps in a DB transaction, creates Contribution rows with status=success, decrements wallet balance, and writes a WalletTransaction debit with source=wallet_allocation and distribution details in meta.


## Security and Idempotency
- Signature Verification:
  - Header: x-paystack-signature
  - Computed: hash_hmac('sha512', raw_body, PAYSTACK_SECRET_KEY)
  - Requests with missing/invalid signature are rejected (400).
- Transaction Verification: every charge.success is verified again via Paystack’s /transaction/verify/{reference}.
- Amount & Currency Checks: webhook must indicate NGN and amount >= expected.
- Idempotency: wallet_transactions.reference is unique, so replays won’t double‑credit.


## API Quick Reference (Authenticated unless noted)
- GET  /api/virtual-account
- POST /api/virtual-account/assign
- GET  /api/wallet
- GET  /api/wallet/transactions?type=credit|debit&page=1&per_page=15
- POST /api/wallet/topup/initiate { amount, callback_url? }
- POST /api/wallet/allocate { items: [{ scheme_id, amount }] }
- POST /api/virtual-account/assign-flutterwave { bvn } (creates Flutterwave DVA)
- POST /api/virtual-account/regenerate-flutterwave { bvn } (forces creation of a new Flutterwave DVA)
- POST /api/webhooks/paystack (public; Paystack calls this)
- POST /api/webhooks/flutterwave (public; Flutterwave calls this)

Example: Assign a DVA (Paystack)
```
curl -X POST \
  -H "Authorization: Bearer <TOKEN>" \
  https://your-domain.tld/api/virtual-account/assign
```

Example: Assign a DVA (Flutterwave)
```
curl -X POST \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"bvn":"12345678901"}' \
  https://your-domain.tld/api/virtual-account/assign-flutterwave
```

Example: Regenerate a DVA (Flutterwave)
```
curl -X POST \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"bvn":"12345678901"}' \
  https://your-domain.tld/api/virtual-account/regenerate-flutterwave
```

Example: Get Wallet (includes virtual_account and flw_virtual_account blocks)
```
curl -H "Authorization: Bearer <TOKEN>" \
  https://your-domain.tld/api/wallet
```


## Troubleshooting
- 401 Unauthorized from Paystack APIs
  - Ensure PAYSTACK_SECRET_KEY is correct and active for your environment.
  - Ensure your business is enabled for Dedicated Accounts by Paystack support.
- Webhook not received / transfers not credited
  - Confirm PAYSTACK_WEBHOOK_URL matches the URL configured on Paystack Dashboard and is publicly reachable (use ngrok for local).
  - Check server logs for "Invalid Signature" (indicates secret mismatch) or network errors.
- Duplicate credits
  - The system guards with unique reference per WalletTransaction; if you still see duplication, verify your provider isn’t retrying with different references.
- Wrong member credited
  - Confirm the Paystack customer_code is stored on the correct user and that the DVA account number matches. The webhook resolver checks customer_code → account_number → metadata.user_id.


## BVN and Verification Details
- When assigning a virtual account, the backend can accept and persist a member’s BVN.
- New fields on users table: bvn (string), bvn_verified_at (timestamp nullable), dva_verification_meta (json nullable).
- API responses:
  - GET /api/profile → includes bvn_assigned (boolean) and verification_details (e.g., "Wema Bank - 0123456789 (John Doe)").
  - GET /api/virtual-account → includes bvn_assigned and verification_details alongside the account details.
  - GET /api/wallet → virtual_account includes bvn_assigned and verification_details.
  - GET /api/dashboard → virtual_account includes bvn_assigned and verification_details.
- Controller behavior:
  - POST /api/virtual-account/assign now accepts optional: bvn, first_name, last_name, phone, preferred_bank.
  - BVN is stored if provided; verification meta is recorded for audit. If provider later returns verified status, bvn_verified_at can be populated by a separate process.
- Frontend: Profile.vue already reads and displays bvn_assigned and verification_details in the Verification section.

## Notes on Providers (Paystack vs Monnify)
- Cooperatives often choose Monnify due to flatter fees and multi‑bank DVAs, but Paystack is fully supported here.
- To switch providers in the future:
  - Replace the DVA assignment/fetch calls in VirtualAccountController with the provider’s equivalents.
  - Adjust webhook parsing to the provider’s payload shape.
  - Keep the same local data model (customer code, bank/account details) and WalletTransaction logic.


## Glossary
- DVA: Dedicated Virtual Account
- NUBAN: Nigerian Uniform Bank Account Number
- Webhook: HTTP callback sent by the provider to notify about events (payments)
- Kobo: 1/100th of a Naira; amounts from Paystack are typically in kobo


## Changelog
- 2026‑03‑20: Initial documentation added describing the current Paystack DVA implementation.
- 2026‑05‑10: Added Flutterwave DVA as alternative provider. New migration (flw_dva_* fields on users), FlutterwaveDvaService, POST /api/virtual-account/assign-flutterwave endpoint, webhook DVA lookup by flw_dva_account_number, flw_virtual_account block in wallet API, and frontend Wallet.vue Flutterwave DVA section.
