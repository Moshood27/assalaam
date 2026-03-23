# Push Notifications (Firebase Cloud Messaging)

This project supports sending push notifications to mobile or web clients using Firebase Cloud Messaging (FCM).

Two drivers are available so you can migrate safely from the legacy HTTP API to the latest HTTP v1 API:

- fcm_v1 — Recommended. Uses Firebase HTTP v1 via Service Account credentials and the Kreait SDK.
- fcm — Legacy HTTP API using a Server Key.
- log — Development-only driver that writes messages to the log instead of sending them.

The backend wiring lives in `backend/app/Services/PushService.php` and is configured via `backend/config/push.php` and `.env` entries.

## Quick start

1) Choose a driver
- Local/dev: `PUSH_DRIVER=log` (no external calls, messages appear in Laravel logs)
- Production (recommended): `PUSH_DRIVER=fcm_v1`
- Legacy fallback: `PUSH_DRIVER=fcm`

2) Enable push
- Set `PUSH_ENABLED=true` in `backend/.env` (or your production environment).

3) Provide credentials depending on the driver
- fcm_v1 (HTTP v1): Set a Service Account JSON file path in either `FIREBASE_CREDENTIALS` or `GOOGLE_APPLICATION_CREDENTIALS`.
- fcm (legacy): Set your `FCM_SERVER_KEY`.

4) Send a test message (from Tinker or a controller)
```php
$svc = app(\App\Services\PushService::class);
$ok = $svc->send('<device_fcm_token>', 'Hello', 'This is a test', ['foo' => 'bar']);
```
If `PUSH_DRIVER=log`, you should see the message in your logs. With `fcm_v1` or `fcm`, the device should receive a notification.

## Environment variables

Place these in `backend/.env` (values are examples):

```
# Master switch
PUSH_ENABLED=true

# Driver: fcm_v1 | fcm | log
PUSH_DRIVER=fcm_v1

# Optional custom log channel (defaults to logging.default)
PUSH_LOG_CHANNEL=

# Legacy HTTP API (only if using PUSH_DRIVER=fcm)
FCM_SERVER_KEY=AAAA...your_server_key...
FCM_BASE_URL=https://fcm.googleapis.com

# HTTP v1 (recommended) - Kreait will use either of these
FIREBASE_CREDENTIALS=/full/path/to/serviceAccount.json
# or
GOOGLE_APPLICATION_CREDENTIALS=/full/path/to/serviceAccount.json
# Alternatives (handled by PushService):
# Provide inline JSON directly (not recommended for production, but useful in CI):
FIREBASE_CREDENTIALS_JSON={"type":"service_account", ...}
# Or provide Base64-encoded JSON (useful for containerized/ephemeral envs):
FIREBASE_CREDENTIALS_BASE64=eyJ0eXBlIjoic2VydmljZV9hY2NvdW50Ii4uLn0=
```

Notes
- Use an absolute path for the Service Account file.
- The same Firebase project must be used by your client apps (Android/iOS/Web) and your server credentials.

## Service Account setup for HTTP v1 (fcm_v1)

1) In the Google Cloud Console for your Firebase project, create a Service Account key (JSON):
   - IAM & Admin > Service Accounts > Select your Firebase Admin SDK service account
   - Keys > Add Key > Create new key (JSON)
2) Save the downloaded JSON on your server and set the path in `FIREBASE_CREDENTIALS` (or `GOOGLE_APPLICATION_CREDENTIALS`).
3) Ensure the Service Account has Firebase Admin permissions for Messaging (default Admin SDK account does).

The backend uses `kreait/laravel-firebase` to obtain a `Messaging` client and sends with high priority and default sounds:
- Highest possible priority (Android/iOS/WebPush)
- Default notification sounds for Android and iOS
- Data payload keys/values are converted to strings as required by FCM v1

## Legacy driver (fcm)

If you can’t move yet to HTTP v1, keep using `PUSH_DRIVER=fcm` and set `FCM_SERVER_KEY`. The backend will call `https://fcm.googleapis.com/fcm/send` with a high-priority notification and default sound.

## Code reference

- Service: `backend/app/Services/PushService.php`
  - `send($toToken, $title, $body, array $data = []): bool`
  - Drivers: `log`, `fcm_v1`, `fcm`
- Config: `backend/config/push.php`

## Usage tips

- Tokens: Ensure the client app registers for push and sends the FCM device token to your API for storage.
- Data payload: For `fcm_v1`, all data values must be strings. Complex values are JSON-encoded by the service.
- Notification vs data-only: You can pass both `notification` (title/body) and `data`. Some platforms handle background delivery only for data messages; your app code may need to surface them.
- Sounds/priority: Already set to default/high in the service. Adjust in code if you need custom behavior per platform.

## Testing

- Development: Use `PUSH_DRIVER=log` so you can verify messages in logs without external calls.
- Staging/Prod: Use `PUSH_DRIVER=fcm_v1` with valid credentials and a real device token.
- Tinker example:
```bash
php artisan tinker
>>> $svc = app(\App\Services\PushService::class);
>>> $svc->send('your_device_token', 'Ping', 'From Tinker!', ['env' => app()->environment()]);
```

## Troubleshooting

- "Push not sent: missing device token": Ensure you pass a non-empty token.
- "Push driver not recognized": Check `PUSH_DRIVER` value.
- FCM v1 errors (Kreait):
  - Ensure `FIREBASE_CREDENTIALS` points to a readable Service Account JSON.
  - The Service Account project must match your client app’s Firebase project.
  - Check server time drift and network egress to Google endpoints.
- Legacy API errors:
  - 401/403: Wrong `FCM_SERVER_KEY`.
  - 400: Malformed payload.

## Security

- Never commit Service Account JSON into version control.
- Restrict file permissions on the credentials file (readable only by the app user).

## Changelog

- 2026-03-21: Added HTTP v1 support (`PUSH_DRIVER=fcm_v1`) alongside legacy `fcm`. This document describes setup and usage.
