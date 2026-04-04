Changelog

All notable changes to this project will be documented in this file.

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
