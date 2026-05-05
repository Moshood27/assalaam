# VPS Update & Deployment Guide (System Migration)

This guide describes how to deploy the new **System Migration** features to an existing VPS environment where the Attaqwa Cooperative app is already running.

## 1) Overview of Changes
The recent update introduces:
- **New Dependencies:** `maatwebsite/excel` (Import/Export) and `barryvdh/laravel-dompdf` (PDF Generation).
- **Database Schema:** New fields in the `users` table (`migrated_at`, `verified_at`, `discrepancy_reported_at`).
- **Admin Tools:** A new **System Migration** page in the Filament Admin Panel.
- **Member Dashboard:** A verification modal for migrated members on first login.

---

## 2) Step-by-Step Upgrade (Docker Production)

If your VPS uses the production Docker stack (`docker-compose.pro.yml`), follow these steps:

### Step A: Pull Latest Code
SSH into your VPS and fetch the updates:
```bash
cd /path/to/cooperative
git pull origin main
```

### Step B: Install Backend Dependencies
Run composer inside the app container to install the new Excel and PDF libraries:
```bash
docker exec -it attaqwa-app composer install --optimize-autoloader --no-dev
```

### Step C: Run Database Migrations
Apply the new fields to the `users` table. This is safe and will not affect existing user data:
```bash
docker exec -it attaqwa-app php artisan migrate --force
```

### Step D: Rebuild Frontend Assets
The frontend changes (modals, reporting) need to be compiled:
```bash
cd frontend
npm install
npm run build
cd ..
```
*Note: Ensure your `proxy` container is serving the updated `frontend/dist` directory.*

### Step E: Clear Caches & Publish Assets
```bash
docker exec -it attaqwa-app php artisan optimize
docker exec -it attaqwa-app php artisan filament:assets
```

### Step F: Clear Filament-Specific Caches (Important)
If you encounter `RouteNotFoundException` or the new page is missing from the sidebar, run:
```bash
docker exec -it attaqwa-app php artisan route:clear
docker exec -it attaqwa-app php artisan config:clear
docker exec -it attaqwa-app php artisan filament:clear-cached-components
docker exec -it attaqwa-app php artisan view:clear
```

---

## 3) Post-Update Checklist

1. **Verify Admin Access:**
   - Log in to `/admin`.
   - Ensure the **System Migration** item appears in the sidebar.
   - Check if you can access the page without errors.

2. **Test PDF Generation:**
   - Go to the System Migration page.
   - Click **Download Audit Report (PDF)**.
   - Ensure a PDF is generated and downloaded correctly (this verifies `dompdf` is working).

3. **Check Logs:**
   If you encounter any "500 Internal Server Error", check the logs:
   ```bash
   docker logs -f attaqwa-app
   ```

---

## 4) Troubleshooting

### "Class 'Maatwebsite\Excel\ExcelServiceProvider' not found"
- This means `composer install` didn't run or failed. Re-run Step B.

### "Column 'migrated_at' not found"
- This means migrations weren't run. Re-run Step C.

### GD Library Errors
- The production `Dockerfile` already includes the `gd` extension. If you are using a custom PHP environment, ensure `php-gd` and `php-zip` are installed and enabled.

### Permissions
- If imports fail with "Permission denied", ensure the `storage/app/public` and `storage/framework/cache` directories are writable by the web server user inside the container.
