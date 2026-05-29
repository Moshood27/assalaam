# KYC & Identity Verification System

This document explains the Know Your Customer (KYC) and identity verification architecture in the Cooperative app, including the supported providers (Dojah, Mock), face match logic, and configuration.

Date: 2026-04-06

---

## 1. Overview

To prevent fraud and ensure compliance, the system requires members to verify their identity using their Bank Verification Number (BVN) and a live selfie. The system compares the selfie provided by the member against the image on file with the national identity database (via the BVN).

---

## 2. Supported Providers

The system uses a driver-based architecture (`KycVerifier`) to support multiple verification providers.

### Dojah (Production)
Dojah is the primary provider for identity verification in Nigeria.
- **Service**: BVN with Face Match.
- **Logic**: It takes the member's BVN and a selfie, then returns a confidence score indicating the likelihood that the person in the selfie is the same person linked to the BVN.

### Mock (Local/Testing)
A mock provider is available for development and testing environments where real BVN data should not be used.
- **Behavior**: Always returns a successful match with a high confidence score without making external API calls.

---

## 3. Configuration

### Environment Variables (`backend/.env`)
```env
# Available providers: mock, dojah
KYC_PROVIDER=mock

# Minimum confidence score to accept face match (0..1)
KYC_FACE_MATCH_MIN=0.82

# Dojah credentials
DOJAH_APP_ID=your_app_id
DOJAH_SECRET=your_secret
DOJAH_BASE_URL=https://api.dojah.io
```

### Confidence Threshold
The `KYC_FACE_MATCH_MIN` variable (default `0.82`) determines the strictness of the face match. If the provider returns a confidence score below this threshold, the verification will be rejected.

---

## 4. Implementation Details

- **Verifier Service**: `App\Services\Kyc\KycVerifier`
- **Provider Interface**: `App\Services\Kyc\Providers\KycProviderInterface`
- **Models**: The verification status and BVN are typically stored on the `User` or a dedicated `Identity` model.

### Workflow
1. Member uploads a selfie and enters their BVN.
2. The `KycVerifier` resolves the active driver (Dojah or Mock).
3. The driver calls the provider's API.
4. The system compares the result against `face_match_min`.
5. If successful, the member is marked as "Verified" and gains access to features like higher withdrawal limits or loan eligibility.

---

## 5. Security & Privacy

- **BVN Storage**: BVNs are sensitive data and should be handled according to local regulations (NDPR/GDPR).
- **Image Handling**: Selfies and ID images are temporarily stored and sent to the provider over encrypted channels.
- **Fail-Safe**: If the provider is unreachable, the system fails closed (rejects verification) unless the Mock provider is active.

---

â€” Last updated: 2026-04-06
