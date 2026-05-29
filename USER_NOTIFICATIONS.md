# User Notification Handling (Client App)

This document explains where and how the app implements user-facing push notifications, i.e., what happens when a user receives or taps a notification.

If you need backend setup and sending details, see PUSH_NOTIFICATIONS.md.

## Where notification logic lives

- Client registration and push setup: frontend/src/main.js (Capacitor mobile runtime)
  - Saves the real FCM device token to the backend at POST /api/user/fcm-token for the logged-in user
  - Requests runtime permission and registers with FCM via @capacitor/push-notifications
- Android native wiring: frontend/android/app/
  - google-services.json (not committed; required for FCM)
  - AndroidManifest.xml already contains the required Firebase/Push declarations
- iOS native wiring: frontend/ios/App/
  - Push capability is enabled via Capacitor; ensure APNs is set up for your Firebase project

## Receiving and handling notifications in the app

By default, main.js registers the device and sends the token to the backend. To also surface messages to users and route on tap, add listeners for these events in the same Capacitor block:

- pushNotificationReceived â€” fired when the app is in the foreground
- pushNotificationActionPerformed â€” fired when the user taps a notification in the system tray (background/resumed)

Example implementation (illustrative; place inside the existing isCapacitor && isLoggedIn block in frontend/src/main.js after the registration listeners):

```js
// Show an in-app banner or log when a notification arrives in foreground
await PushNotifications.addListener('pushNotificationReceived', (notification) => {
  try {
    const data = notification?.data || {}
    const title = notification?.title || notification?.notification?.title || 'Notification'
    const body = notification?.body || notification?.notification?.body || ''
    console.log('[push] received (fg):', { title, body, data })
    // Optional: Trigger your own UI notice/toast/modal here
    // window.dispatchEvent(new CustomEvent('notice', { detail: { title, body, data } }))
  } catch (e) {
    console.warn('Error handling received notification', e)
  }
})

// Route the user when they tap the notification from the tray
await PushNotifications.addListener('pushNotificationActionPerformed', (event) => {
  try {
    const data = event?.notification?.data || {}
    console.log('[push] action performed:', data)

    // Convention: backend includes a destination in data.route (e.g., '/store/orders/123')
    const route = (data.route || data.screen || '').toString()
    if (route) {
      router.push(route)
      return
    }

    // Fallback example based on common payload keys
    if (data.orderId) {
      router.push(`/store/orders/${data.orderId}`)
      return
    }

    // Default landing
    router.push('/dashboard')
  } catch (e) {
    console.warn('Error handling notification action', e)
  }
})
```

Notes:
- Keep values in the FCM data payload as strings (FCM v1 requirement). Complex objects should be JSON-encoded server-side.
- For iOS, foreground notifications might not show a system banner by default; use the in-app UI approach to inform users.

## Expected payloads from the server

The backend PushService supports both notification (title/body) and data payloads. Example HTTP v1 shape the app understands well:

```json
{
  "notification": { "title": "Order update", "body": "Your order #123 has shipped" },
  "data": {
    "route": "/store/orders/123",
    "orderId": "123",
    "type": "order_update"
  }
}
```

- Prefer the route key for client-side navigation. The app will fall back to other hints if provided.

## End-to-end flow

1) User logs in; the app requests push permission and registers with FCM.
2) On successful registration, the app posts the FCM token to POST /api/user/fcm-token.
3) Backend stores the token per user and can later call App\Services\PushService::send(...) to deliver messages.
4) When a notification arrives:
   - If app is foreground: pushNotificationReceived fires; surface it via UI.
   - If user taps from system tray: pushNotificationActionPerformed fires; route based on data.route or other keys.

## Platform prerequisites

Android
- Place google-services.json under frontend/android/app/google-services.json (never commit it).
- Build script warns if the file is missing: android/app/build.gradle.

iOS
- Enable Push Notifications capability and configure APNs for your Firebase project.
- Ensure the iOS app is registered in Firebase and the GoogleService-Info.plist is properly configured via Capacitor.

Web (optional)
- Web push is not configured by default. To support web:
  - Add a service worker with Firebase Messaging or the Web Push API.
  - Request Notification and PushManager permissions.

## Troubleshooting

- No device token stored: Ensure the user is logged in before push setup runs and that POST /api/user/fcm-token is reachable and authorized.
- Permission denied: The app will not register; show a help screen to re-enable notifications in system settings.
- Tapping a notification does nothing: Check that data.route (or your chosen keys) are present and valid for your router.
- iOS foreground banners: Implement in-app UI as shown; iOS suppresses system banners by default when foregrounded.

## Related docs

- Backend sending and configuration: PUSH_NOTIFICATIONS.md
- Android configuration: frontend/android/app/ (AndroidManifest.xml, build.gradle)
- iOS configuration: frontend/ios/App/

## Changelog
- 2026-03-25: Initial document explaining where and how user notification handling is implemented.
