# Local Paystack Webhooks with ngrok (Windows, no Docker)

Use the native Windows ngrok app to receive Paystack webhooks on your local Laravel app over HTTPS. This avoids Docker image pulls and is generally more stable on Windows.

Prerequisites
- A free ngrok account (to obtain an auth token)
- Laravel backend running locally (e.g., `php artisan serve` on port 8000, or IIS/Apache on port 80)
- backend/.env configured with your Paystack keys: `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY`

1) Install and run ngrok (Windows)
- Download ngrok for Windows from https://ngrok.com/download
- Unzip and run `ngrok.exe`
- In the opened console, authenticate once (replace TOKEN):
  - `ngrok config add-authtoken TOKEN`
- Start an HTTP tunnel to your Laravel port:
  - If using `php artisan serve`: `ngrok http 8000`
  - If serving on port 80: `ngrok http 80`
- Copy the HTTPS Forwarding URL shown (e.g., `https://xxxx.ngrok-free.app`)

2) Tell Laravel itâ€™s behind a proxy (required for ngrok)
We already configured this in code. Verify `backend/bootstrap/app.php` contains:

```
->withMiddleware(function (Middleware $middleware) {
    // This allows Ngrok to send data to Laravel
    $middleware->trustProxies(at: '*');

    // This stops Laravel from asking Paystack for a CSRF token
    $middleware->validateCsrfTokens(except: [
        'api/webhooks/paystack'
    ]);
})
```

3) Configure Paystack Webhook
- In Paystack Dashboard â†’ Settings â†’ API â†’ Webhooks
- Set URL to: `https://YOUR_NGROK_SUBDOMAIN.ngrok-free.app/api/webhooks/paystack`
- Save

4) Fix TLS "bad record MAC" on some Windows networks (MTU)
Some networks/drivers cause TLS errors when tunneling. Setting MTU to 1350 often resolves this.
- Open an elevated PowerShell and run:
  - `netsh interface ipv4 show interfaces`  (note your active InterfaceIdx and Name)
  - `netsh interface ipv4 set subinterface "YOUR INTERFACE NAME" mtu=1350 store=persistent`
- Reconnect the interface (or reboot). Then re-open ngrok and try again.

5) Test end-to-end
- From the Vue app, initialize a payment or top up the wallet; Paystack checkout should open without TLS errors.
- Make the payment and watch webhook deliveries in the ngrok inspector (`http://127.0.0.1:4040`) and Laravel logs.

Notes
- Webhook endpoint: `POST /api/webhooks/paystack` with `X-Paystack-Signature` verification using `PAYSTACK_SECRET_KEY`.
- If you see â€œInvalid Signatureâ€, ensure the secret matches the Paystack environment (Test vs Live) used by the event.
- Ngrok URLs change every run unless you have a reserved domain (paid). Update the webhook URL in Paystack when it changes.
- If your Laravel runs on a different port, adjust the `ngrok http <port>` command accordingly.

(Previous Docker-based ngrok instructions have been replaced by these Windows-native steps.)
