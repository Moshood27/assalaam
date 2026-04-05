# Guide: Transition/Deploy from ATTAQWA to ASSALAM

This guide explains how to switch this mono‑repo (Laravel backend + Vue/Capacitor frontend + mobile) from the ATTAQWA brand to the ASSALAM brand, and how to deploy it. It consolidates steps already covered across DEPLOYMENT.md, MOBILE_BUILD_SYNC.md, and other docs into a single, actionable checklist.

Use this when:
- You are rebranding the same codebase for ASSALAM.
- You are deploying an ASSALAM instance (web and/or mobile).

If you are also migrating production data, see the Optional: Data migration section.

---

## 0) Prerequisites
- Node.js 18+ and npm
- PHP 8.2+, Composer
- Docker + Docker Compose (recommended) OR native PHP server stack
- Android Studio / Xcode if building mobile
- Access to appropriate infrastructure (domain, SSL, DB, object store, queue)

---

## 1) Create brand‑specific environment files

Brand is controlled by the environment variables BRAND_SLUG (backend) and VITE_BRAND_SLUG (frontend). Many assets and defaults are driven by these values.

1. Backend .env (Laravel)
   - Copy backend/.env.example to backend/.env (if not present).
   - Edit these keys for ASSALAM:

     Example:
     APP_NAME="ASSALAM CO-OPERATIVE"
     BRAND_SLUG=assalam
     APP_URL=https://<your-assalam-domain>
     ASSET_URL=https://<your-assalam-domain>

     # Database (use a separate DB for ASSALAM)
     DB_DATABASE=coop_assalam
     DB_USERNAME=sail_assalam
     DB_PASSWORD=pass_assalam

     # Queues/broadcasting (update to your hostnames)
     REVERB_HOST=<your-assalam-domain>

     # Webhooks (must match your public domain)
     PAYSTACK_WEBHOOK_URL=https://<your-assalam-domain>/api/webhooks/paystack
     VTU_WEBHOOK_URL=https://<your-assalam-domain>/api/vtu/webhook

     # FCM (if used)
     FCM_PROJECT_ID=assalamcoop-e0dbe

   - Review other service credentials (mail, cache, redis, paystack keys, etc.) and set ASSALAM‑specific values.

2. Frontend .env (Vite/Capacitor)
   - Copy frontend/.env.example to frontend/.env (if not present).
   - Set:

     VITE_BRAND_SLUG=assalam
     VITE_API_BASE=https://<your-assalam-domain>/api
     VITE_APP_BASE_URL=https://<your-assalam-domain>

   - If you maintain separate environments (staging/prod), create corresponding .env.* files as needed.

Notes
- The default fallbacks in code favor ASSALAM in some places, but explicit env is recommended.
- Changing BRAND_SLUG/VITE_BRAND_SLUG is the canonical way to switch all brand names, colors and logos.

---

## 2) Generate branded assets (web + mobile)

UI look & feel separation
- The SPA now applies brand-specific styling at runtime based on VITE_BRAND_SLUG (frontend) and BRAND_SLUG (backend for server-rendered images).
- ASSALAM (default) uses an emerald/teal palette; ATTAQWA applies an indigo palette and brand-specific icons.
- How it works (frontend):
  - frontend/src/brand.js exposes brand.slug, brand.name, brand.shortName, brand.logo, brand.favicon, brand.themeColor.
  - frontend/src/main.js adds a body class brand-{slug} and sets the document title, favicon, and meta theme-color.
  - frontend/src/style.css contains .brand-attaqwa overrides that remap common emerald utility classes (bg/text/border) to an indigo scheme and tweak component tokens (btn-primary, badges, segmented controls, etc.).
- To switch brand UI:
  - Set VITE_BRAND_SLUG=assalam (emerald theme) or VITE_BRAND_SLUG=attaqwa (indigo theme) in frontend/.env, then rebuild.
  - For mobile, also run the asset generator to update icons/splash.

Use the provided scripts to generate and sync brand assets across PWA/Android/iOS.

From the repository root:
- Generate branded placeholders only:
  npm run assets:brand:assalam

- Generate and sync mobile assets to both platforms:
  npm run mobile:assets:sync:assalam

Platform‑specific:
- Android only:
  npm run mobile:assets:android:assalam
- iOS only (run on macOS):
  npm run mobile:assets:ios:assalam

Where this goes
- Frontend PWA icons/splash are updated via frontend/public/manifest.webmanifest.
- Mobile asset generation updates frontend/android and frontend/ios/App assets.
- Backend/public/images contains assalam-*.svg artwork if you need server‑rendered logos.

Troubleshooting
- The generator reads BRAND_SLUG/VITE_BRAND_SLUG from .env files and also accepts --brand assalam. If assets don’t change, confirm env values and re‑run with --force (already included by these scripts).

---

## 3) Web build and deployment (PWA + Laravel)

A. Build the frontend (PWA)
1) In frontend/, install and build:
   npm install
   npm run build
   # Output goes to frontend/dist

B. Deploy with Docker (recommended)
- See docker-compose.yml or docker-compose.pro.yml.
- Typical sequence (from project root):
  docker compose pull
  docker compose build --no-cache
  docker compose up -d

C. Laravel backend bootstrapping (inside the app container or your host):
- Install and optimize:
  composer install --no-dev --optimize-autoloader
  php artisan key:generate   # if APP_KEY is empty
  php artisan migrate --force
  php artisan storage:link
  php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
  php artisan config:cache && php artisan route:cache

- Queues/scheduler:
  php artisan queue:restart
  # Ensure a process manager (e.g., supervisor) runs: php artisan queue:work --sleep=3 --tries=3
  # Ensure cron runs: php artisan schedule:run (and cron entry * * * * * ...)

- Broadcasting/websockets (Reverb/Pusher):
  - Update REVERB_* or PUSHER_* env to the ASSALAM host.
  - If self‑hosting websockets, deploy the service and open needed ports.

- Webhooks:
  - Update provider dashboards (e.g., Paystack) to point to your ASSALAM domain/webhook paths.

D. Point your web server (Nginx/Apache) to serve:
- Public web root: backend/public
- Static PWA assets: ensure APP_URL/ASSET_URL are correct so asset helpers resolve.
- TLS: install SSL for the ASSALAM domain.

Verification
- Visit https://<your-assalam-domain> and confirm the welcome page shows ASSALAM branding/logo.
- Login and perform a no‑op action to confirm API base and CORS are OK.

---

## 4) Mobile app builds (Android/iOS)

A. Capacitor sync
From repo root:
  npm run cap:sync
# Or re‑run the all‑in‑one to refresh assets and sync:
  npm run mobile:assets:sync:assalam

B. App name and identifiers
- capacitor.config.json currently has appName set to "ATTAQWA". For an ASSALAM build, either:
  - Temporarily change appName to "ASSALAM" before a release build; OR
  - Maintain a branch or patch for brand‑specific appName/IDs.

- Android: Update applicationId and app label if you plan to publish side‑by‑side with ATTAQWA.
  - Open in Android Studio: npx cap open android
  - AndroidManifest.xml / app build.gradle: set applicationId (e.g., com.assalam.cooperative) and label.

- iOS: Update Bundle Identifier and Display Name in Xcode.
  - npx cap open ios

C. Build
- Android (debug):
  cd frontend/android
  ./gradlew assembleDebug

- Android (release):
  Configure signing in android/app and run assembleRelease.

- iOS (debug/release):
  Open Xcode workspace under frontend/ios/App and archive appropriately.

Verification
- Launch the app and confirm splash/logo and theme reflect ASSALAM.

---

## 5) Optional: Data migration from ATTAQWA to ASSALAM

If ASSALAM is a separate instance and should start with data from ATTAQWA:
1) Snapshot ATTAQWA database.
2) Restore into the new ASSALAM database name (e.g., coop_assalam).
3) Review any brand‑specific rows you may want to change (e.g., seeded texts). Core tables are generally brand‑agnostic.
4) Rotate secrets and webhooks to ASSALAM values.

Caution: Never point two brands to the same production DB.

---

## 6) Post‑deployment checklist
- [ ] backend/.env has BRAND_SLUG=assalam and correct APP_URL/ASSET_URL
- [ ] frontend/.env has VITE_BRAND_SLUG=assalam and correct API base
- [ ] npm run assets:brand:assalam (and/or mobile:assets:sync:assalam) run successfully
- [ ] Frontend PWA built and deployed
- [ ] Laravel key generated, migrations run, caches optimized
- [ ] Queue workers and scheduler running
- [ ] Webhooks updated at providers
- [ ] Reverb/Pusher broadcasting working against ASSALAM host
- [ ] SSL valid on ASSALAM domain
- [ ] Basic end‑to‑end test passed (login, simple transaction or fetch)

---

## 7) Troubleshooting
- Assets didn’t change:
  - Confirm BRAND_SLUG/VITE_BRAND_SLUG in both backend/.env and frontend/.env.
  - Re‑run: npm run assets:brand:assalam

- PWA shows old logo:
  - Clear browser cache; verify frontend/dist is freshly built and deployed.

- Mobile shows old splash/icon:
  - Re‑run npm run mobile:assets:sync:assalam and then npx cap sync. Clean/rebuild projects in Android Studio/Xcode.

- API calls fail (CORS or 404):
  - Check VITE_API_BASE/VITE_APP_BASE_URL and server CORS configuration.

- Webhooks failing:
  - Check provider has updated URLs and server is reachable (use curl on the webhook URL).

---

## 8) References
- MOBILE_BUILD_SYNC.md: brand asset generation details and scripts
- DEPLOYMENT.md: general deployment guidance (Docker and native)
- assets/README.md: how icons/splash are generated
- backend/config/brand.php: brand config mapping
- frontend/src/brand.js: runtime branding in the SPA
- scripts/generate-branded-assets.mjs: asset generator and how BRAND_SLUG is detected
