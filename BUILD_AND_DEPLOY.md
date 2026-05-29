# Build and Deploy Guide (Web + Mobile)

This is a concise, copyâ€‘paste friendly guide to build and deploy the web app and the native mobile apps. For deeper, productionâ€‘grade notes, see DEPLOYMENT.md and MOBILE_BUILD_SYNC.md.

Contents
- Prerequisites
- Local development (backend + frontend)
- Web build and deploy (served under /app/)
- Mobile build and run (Android/iOS)
- Quick commands
- Troubleshooting

Prerequisites
- Node.js 20+ and npm
- Docker Desktop (with Docker Compose v2)
- Android Studio (Java 17 + Android SDK) for Android builds
- macOS + Xcode for iOS builds (optional)
- If using WSL2: run Android Studio/Xcode on the host OS; you can build the web inside WSL2

Project layout (relevant folders)
- backend/ â€” Laravel API
- frontend/ â€” Vue 3 + Vite app (Capacitor for mobile)
- capacitor.config.json â€” points to frontend/dist and native project paths
- docker-compose.pro.yml â€” Nginx + PHPâ€‘FPM + MySQL production-ish stack
- docker/nginx/conf.d/default.conf — Nginx routes web app from /app/

1) Local development
Backend (Laravel Sail wrappers from repo root)
- Start: ./sail up -d   (PowerShell: .\sail.ps1 up -d)
- Stop:  ./sail down
- Logs:  ./sail logs -f
- Migrate: ./sail artisan migrate

Frontend (Vite dev server)
- cd frontend
- npm install
- Optional: set API proxy target if your backend is not at the default
  - Linux/macOS: VITE_PROXY_TARGET=http://localhost:8080 npm run dev
  - Windows PowerShell: $env:VITE_PROXY_TARGET="http://localhost:8080"; npm run dev
- Default dev URL: http://localhost:5174

Notes
- Vite proxy reads VITE_PROXY_TARGET (default http://localhost:8000). If your Laravel (Sail) is on http://localhost:8080, set VITE_PROXY_TARGET accordingly as shown above.

2) Web build and deploy (served under /app/)
The web app is designed to be served from /app/ (see nginx default.conf). The Vite config already uses base '/app/' for normal builds.

Build web assets
- cd frontend
- npm install (first time only)
- npm run build   # outputs to frontend/dist

Run with Docker (nginx + php-fpm + mysql)
- From repo root, ensure backend/.env is configured (DB_*, APP_URL, etc.)
- docker compose -f docker-compose.pro.yml up -d --build

What happens
- Nginx serves the SPA from /app/ using the volume mapping: ./frontend/dist â†’ /var/www/html/public/app
- Laravel API is available at the root (/) and /api routes. See docker/nginx/conf.d/default.conf for details.
- Visit http://localhost (or your server host). The web app is under http://HOST/app/

Updating the deployed web app
- Rebuild frontend: npm --prefix frontend run build
- Restart or reload Nginx stack if needed: docker compose -f docker-compose.pro.yml restart proxy

3) Mobile build and run (Android/iOS)
The mobile build uses a different Vite base ('./') to prevent white screens in WebViews. A dedicated script is provided.

Set the API URL for devices/emulators
- Decide the backend origin reachable from the device:
  - Android Emulator: http://10.0.2.2:8080
  - Physical device on same LAN: http://YOUR_LAN_IP:8080
  - iOS Simulator (on macOS): http://127.0.0.1:8080 or http://YOUR_MAC_LAN_IP:8080
- Option A: create frontend/.env.mobile with e.g.
  - VITE_API_URL=http://YOUR_LAN_IP:8080
- Option B: set it inline when building (examples below)

Build web assets for mobile (uses base: './')
- cd frontend
- npm run build:mobile     # equivalent to: vite build --mode mobile
  - Or inline vars:
    - Linux/macOS: VITE_API_URL=http://YOUR_LAN_IP:8080 npm run build:mobile
    - Windows PowerShell: $env:VITE_API_URL="http://YOUR_LAN_IP:8080"; npm run build:mobile

Sync and open native projects (run from repo root unless noted)
- npx cap sync android
- npx cap open android     # opens Android Studio
- On macOS for iOS:
  - npx cap sync ios
  - npx cap open ios       # opens Xcode

Run from Android Studio / Xcode
- Ensure the device/emulator can reach the API origin you configured in VITE_API_URL.

Notes
- Router: on native (Capacitor), the app uses hash history to avoid path issues.
- Splash: the native splash auto-hides after the app is ready. If you see the splash remain, confirm you built with build:mobile and synced.

4) Quick commands
Common web deploy
- npm --prefix frontend run build
- docker compose -f docker-compose.pro.yml up -d --build

Common Android cycle
- npm --prefix frontend run build:mobile
- npx cap sync android
- npx cap open android

Clean Android debug APK (from frontend/android)
- ./gradlew assembleDebug    (PowerShell: .\gradlew.bat assembleDebug)
- Output: frontend/android/app/build/outputs/apk/debug/app-debug.apk

5) Troubleshooting
- White screen in Android build
  - Make sure you used: npm run build:mobile (base './') and reâ€‘ran npx cap sync android
- API calls failing on device
  - Use a reachable origin (e.g., http://YOUR_LAN_IP:8080). Configure VITE_API_URL before building.
- 404s on web under /app/
  - Ensure you deployed the dist folder to /public/app and are accessing http://HOST/app/
- Dev server cannot reach backend
  - Set VITE_PROXY_TARGET to your backend origin (e.g., http://localhost:8080) before npm run dev

Further reading
- DEPLOYMENT.md â€” comprehensive Docker, production hardening, and mobile notes
- MOBILE_BUILD_SYNC.md â€” detailed Capacitor build/sync workflow and asset generation
