Frontend guide: Pay with Attaqwa (Merchant QR)

Date: 2026-04-04

Overview
- This guide explains how members use the mobile app to receive and pay via “Pay with Attaqwa” QR codes.
- It complements MERCHANT_API.md (backend API and payload format). See: MERCHANT_API.md.

What’s included
- Two new member screens in the Vue app:
  - MerchantReceive.vue: Generate a QR for others to scan. Optional amount and note.
  - MerchantPay.vue: Scan or paste a QR payload, confirm the merchant, enter amount and 4‑digit PIN, and pay.
- Native QR scanning using @capacitor-mlkit/barcode-scanning (Android and iOS).

Prerequisites
- Node 18+ and a working Capacitor setup.
- iOS or Android environment (Xcode/Android Studio) if testing native camera scanning.

Install and sync mobile dependencies
1) cd frontend && npm install
2) Ensure @capacitor-mlkit/barcode-scanning is present in package.json (added by this repo):
   - "@capacitor-mlkit/barcode-scanning": "^6.2.0"
3) Sync Capacitor projects:
   - npx cap sync
4) Open the native project and build:
   - Android: npx cap open android (Android Studio)
   - iOS: npx cap open ios (Xcode)

App routes and entry points
- Receive via QR: /merchant/receive
- Pay a merchant: /merchant/pay
- Quick-access buttons are also shown on the Wallet screen.

Runtime permissions
- iOS: The app asks for camera permission during first scan. Info.plist includes:
  - NSCameraUsageDescription = "We use the camera to scan merchant QR codes for payments."
- Android: No extra manifest entries required for the ML Kit plugin; runtime camera permission is requested automatically.

Usage flows
A) MerchantReceive (member acting as a merchant)
- Optional inputs: amount (suggested), note (<= 120 chars).
- Tap “Generate QR” to receive a scannable payload using the backend endpoint GET /api/merchant/pay/qr.
- The QR string follows the custom scheme: attaqwa:pay?... (see MERCHANT_API.md for details).
- Show the QR for the payer to scan, or copy/share the payload as text.

B) MerchantPay (member paying a merchant)
- Tap “Scan QR” to open the camera and scan a QR code payload. Alternatively, paste a payload string.
- The app calls POST /api/merchant/pay/resolve to:
  - Validate payload structure and resolve the merchant.
  - Handle branch disambiguation if a membership number is reused across branches (UI will prompt for branch).
- Enter amount if not prefilled by the QR. Enter a short note (optional).
- Enter 4‑digit transaction PIN.
- On “Pay”, the app calls POST /api/merchant/pay which delegates to /api/wallet/transfer for PIN verification, balance checks, and ledger entries.

Amount precedence
- Explicit amount entered in MerchantPay overrides the QR’s amount.
- If neither is provided, the backend returns 422 (Amount is required).

Error handling (typical cases)
- 422 Invalid QR payload: Show message and let user re-scan or paste again.
- 422 Multiple members found: Display the branches list and let the user pick, then retry resolve/pay with branch_id.
- 404 Recipient not found: Inform user.
- 403/409/422 from /api/wallet/transfer: Invalid PIN, insufficient balance, etc.

Mobile build tips
- If the scanner view doesn’t appear on Android:
  - Run npx cap sync android after npm install.
  - Ensure Google Play Services is up-to-date on the test device.
- If iOS build fails after adding the plugin:
  - Open ios/App in Xcode, resolve Swift package updates if prompted, then Clean Build Folder and rebuild.
- If the camera permission prompt doesn’t show on iOS:
  - Confirm NSCameraUsageDescription exists in Info.plist (already added in this repo at frontend/ios/App/App/Info.plist).

Developer notes
- The QR scheme and parameters are documented in MERCHANT_API.md.
- The backend routes live under /api/merchant/* and require auth:sanctum + inactivity middleware.
- For web (PWA), paste-to-pay works; camera scanning is only available in native (Capacitor) builds.
