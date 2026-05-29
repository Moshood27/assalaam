# Telescope & Horizon â€“ Monitoring and Debugging

This document explains how to use Laravel Telescope and Horizon in this project for monitoring background jobs, debugging webhooks, and ensuring system reliability in both local and production environments.

Date: 2026-04-06

---

## 1. Monitoring Dashboards

The project includes two essential monitoring tools accessible to authorized administrators:

- **Laravel Telescope**: A powerful debug assistant that records all requests, jobs, exceptions, and database queries.
- **Laravel Horizon**: A beautiful dashboard and code-driven configuration for Laravel's Redis-powered queues.

### Access URLs
By default, these tools are prefixed with `app/` to avoid conflicts with other routes:
- **Telescope**: `${APP_URL}/app/telescope`
- **Horizon**: `${APP_URL}/app/horizon`

### Authorization
Access is restricted by email address via the `viewTelescope` and `viewHorizon` gates in their respective service providers.
- **Configuration**: Set the authorized emails in your `backend/.env` file.
  - `TELESCOPE_EMAILS="admin@assalaam.com,other@example.com"`
  - `HORIZON_EMAILS="admin@assalaam.com"`

---

## 2. Laravel Telescope (Debugging)

Telescope is configured to record selectively in production to balance insight with performance.

### What is Recorded
- **Local Environment**: Everything (requests, queries, logs, etc.).
- **Production Environment**:
  - Reportable exceptions and failed requests/jobs.
  - Scheduled tasks.
  - **Logs**: All application logs.
  - **Webhooks**: Any request containing `webhook` or `callback` in the URI (e.g., Paystack, VTpass).
  - **Background Jobs**: All job executions (both success and failure).

### Webhook Monitoring
To simplify tracking, all webhook-related requests are automatically tagged with the `webhook` tag. You can filter the "Requests" tab in Telescope by this tag to see all inbound signals from providers.

### Sensitive Data
Telescope is configured to hide sensitive headers like `x-paystack-signature` and `x-flutterwave-signature` in non-local environments to prevent leaking secrets in logs.

---

## 3. Laravel Horizon (Queues)

Horizon monitors the status of your background queues, which handle tasks like sending emails, processing loan auto-recoveries, and finalizing webhook transactions.

### Key Metrics
- **Job Throughput**: Number of jobs processed per minute.
- **Wait Time**: How long jobs sit in the queue before being picked up.
- **Failed Jobs**: A list of all jobs that crashed, including the stack trace and the exact payload that caused the failure. You can retry failed jobs directly from the dashboard.

### Balancing Strategy
Horizon is configured with a `simple` balancing strategy in production, ensuring that workers are distributed across queues (e.g., `default`, `high`) based on current demand.

---

## 4. Configuration & Deployment

### Environment Variables
Key variables in `backend/.env`:
```env
# Path overrides
TELESCOPE_PATH=app/telescope
HORIZON_PATH=app/horizon

# Access control
TELESCOPE_EMAILS="admin@assalaam.com"
HORIZON_EMAILS="admin@assalaam.com"

# Storage
TELESCOPE_ENABLED=true
```

### Deployment Commands
When deploying updates, the following commands are automatically run via `composer.json` scripts:
- `php artisan telescope:publish`: Updates the Telescope assets (JS/CSS).
- `php artisan horizon:publish`: Updates the Horizon assets.

**Note**: In production, ensure the `horizon` worker is running. This is managed via Docker Compose (`assalaam-worker` service) which runs `php artisan horizon`.

---

## 5. Troubleshooting

- **403 Forbidden**: Your email is not in the authorized list in `.env`.
- **404 Not Found**: Assets might not be published. Run `php artisan telescope:publish` and `php artisan horizon:publish`.
- **Jobs are Pending**: Check Horizon to ensure the worker is active and there are no network issues connecting to Redis.
- **Missing Webhook Entries**: Ensure the provider is hitting the correct URL and that `TELESCOPE_ENABLED` is true.

---

â€” Last updated: 2026-04-06
