# Docker Push and Mobile Deployment Guide

This guide explains how to:
- Build and push Docker images for this project
- Run the stack from your registry images
- Package and deploy the mobile app (Android/iOS) using Capacitor
- Access your Dockerized backend from a phone or emulator

Project layout (relevant parts):
- backend/ — Laravel API (uses Laravel Sail in Docker Compose)
- frontend/ — Vue 3 + Vite app (with Capacitor scripts)

Prerequisites
- Docker Desktop 4.x (includes Docker Compose v2)
- A container registry account (e.g., Docker Hub, GHCR, ECR)
- Node.js 20+ and npm (for building the frontend/mobile shell)
- Android Studio for Android builds; Xcode for iOS builds (on macOS)

1) Configure environment
- Copy and edit backend/.env if you haven’t:
  - cp backend/.env.example backend/.env
- Set these in backend/.env as needed:
  - APP_NAME, APP_ENV, APP_KEY (run php artisan key:generate inside container after first boot)
  - APP_URL=http://YOUR_HOSTNAME_OR_IP
  - DB_* credentials (Compose provides MySQL service by default)
  - SANCTUM_STATEFUL_DOMAINS and SESSION_DOMAIN if you plan cookie auth (not required for token/Bearer)
- First run will generate vendor and cache. With Sail, that happens in-container.

2) Build Docker images (dev stack via Sail)
The included Compose files under backend/ use Laravel Sail and a Node 20 container for the Vue dev server. Build the images locally:
- docker compose -f backend/docker-compose.yml build
  - This builds the laravel.test service image based on Laravel Sail’s runtime (image name: sail-8.5/app)
  - MySQL/Redis/Mailpit use official images (no build).

3) Tag and push images to your registry
Decide your registry/image names and tag, for example:
- REG=ghcr.io/your-user-or-org
- TAG=v1
- Backend image produced by build is sail-8.5/app. Tag and push it:
  - docker tag sail-8.5/app ${REG}/cooperative-backend:${TAG}
  - docker push ${REG}/cooperative-backend:${TAG}
Notes
- The frontend service in the provided dev Compose uses node:20-alpine and runs npm run dev (no custom image). If you want a production frontend image, see section 6.
- MySQL/Redis are public images; you typically don’t re-push them.

4) Run stack using your pushed backend image (optional)
If you want Compose to use your remote image instead of building locally, you can create an override file backend/docker-compose.override.yml like:

services:
  laravel.test:
    image: REGISTRY/cooperative-backend:TAG
    build: null

Then run:
- docker compose -f backend/docker-compose.yml -f backend/docker-compose.override.yml up -d

Replace REGISTRY and TAG accordingly. Remove build so Compose pulls your pushed image.

5) Access from your phone on the same network
- Choose a host port for the backend. By default, backend/docker-compose.yml maps APP_PORT (default 8080) to container 80. If port 8080 is in use on your computer, set APP_PORT to another free port in backend/.env and restart Compose.
- Find your computer’s LAN IP address (e.g., 192.168.1.50). From your phone on the same Wi‑Fi:
  - API base URL will be http://192.168.1.50:8080 (or your chosen APP_PORT)
- For local-only testing, you can keep HTTP. For production and iOS TestFlight/App Store, use HTTPS with a valid certificate (via reverse proxy like Traefik/Caddy/Nginx or a cloud load balancer).

6) Optional: Build a production frontend image
For production web deployment (not required for mobile app), you can build the Vue app and serve with Nginx. Example Dockerfile (place it at frontend/Dockerfile if you decide to use it):

# Build stage
FROM node:20-alpine AS build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Serve stage
FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html
# If your app needs SPA routing, add a custom nginx.conf to route 404s to index.html

Build, tag, and push:
- docker build -t ${REG}/cooperative-frontend:${TAG} -f frontend/Dockerfile frontend
- docker push ${REG}/cooperative-frontend:${TAG}

In production, you would front both services with a reverse proxy and configure CORS appropriately.

7) Mobile app (Capacitor) — Android/iOS
The frontend is already set up with Capacitor scripts. The app uses axios with a baseURL taken from VITE_API_URL (see frontend/src/http.js). Steps:

7.1 Configure API URL for mobile builds
- Decide what the backend origin is for the device/emulator:
  - Android Emulator: http://10.0.2.2 (maps to host 127.0.0.1)
  - iOS Simulator (macOS): http://127.0.0.1 or your Mac’s LAN IP
  - Physical device: http://YOUR_COMPUTER_LAN_IP (e.g., http://192.168.1.50)
- Create a mobile .env file for the Vite build, e.g. frontend/.env.mobile:
  - VITE_API_URL=http://192.168.1.50
- When building the web assets for Capacitor, load that env file:
  - npm run build --prefix frontend -- --mode mobile
  - Or simply export VITE_API_URL before build:
    - On Linux/macOS: VITE_API_URL=http://192.168.1.50 npm run build --prefix frontend
    - On Windows PowerShell: setx VITE_API_URL "http://192.168.1.50"; then restart the shell and run npm run build --prefix frontend

7.2 Add platforms and sync
From project root or the frontend directory:
- cd frontend
- npm install
- npx cap add android
- npx cap add ios   # only on macOS with Xcode installed
- npx cap sync

7.3 Open the native projects
- Android: npx cap open android (opens Android Studio)
- iOS: npx cap open ios (opens Xcode)

7.4 Network security considerations
- Android 9+ blocks cleartext HTTP by default. For development you can:
  - Use HTTPS on your backend, OR
  - Add a network_security_config.xml allowing cleartext to your LAN host, and reference it in AndroidManifest.xml (Android Studio -> app/src/main/res/xml/). Plenty of guides exist; search "android allow cleartext traffic network_security_config".
- iOS requires App Transport Security (ATS) exceptions for non-HTTPS. Prefer HTTPS in production.

7.5 Build and run on device/emulator
- Android: Run from Android Studio. Ensure the device can reach http://YOUR_COMPUTER_LAN_IP:APP_PORT
- iOS: Run from Xcode. Ensure the simulator/device can reach your backend origin.

8) Using the dev Vue server from another device (optional)
If you want to open the Vite dev server from your phone on LAN, ensure it binds to 0.0.0.0 and uses an exposed port:
- In a local terminal in frontend/:
  - npm run dev -- --host 0.0.0.0 --port 5174
- If you use the provided frontend service in backend/docker-compose.yml, update its command accordingly (or run Vite directly on your host). Then browse to http://YOUR_COMPUTER_LAN_IP:5174 on your phone.

9) CORS and auth notes
- The frontend uses Bearer tokens via Authorization headers (see frontend/src/http.js). This works well with mobile apps and avoids cookie/CSRF complexities.
- Ensure Laravel CORS (config/cors.php) allows the mobile app origins you use during development (e.g., http://localhost:5174, http://YOUR_COMPUTER_LAN_IP:5174). For pure Capacitor apps installed on device, the scheme is capacitor://localhost.

10) Quick commands reference
- Build dev images: docker compose -f backend/docker-compose.yml build
- Start stack: docker compose -f backend/docker-compose.yml up -d
- Stop stack: docker compose -f backend/docker-compose.yml down
- Backend image tag/push:
  - docker tag sail-8.5/app REGISTRY/cooperative-backend:TAG
  - docker push REGISTRY/cooperative-backend:TAG
- Frontend production image (optional):
  - docker build -t REGISTRY/cooperative-frontend:TAG -f frontend/Dockerfile frontend
  - docker push REGISTRY/cooperative-frontend:TAG
- Capacitor mobile:
  - cd frontend && npm i && npx cap add android && npx cap sync && npx cap open android

Troubleshooting
- Container can’t bind port 80: Set APP_PORT=8080 (or another free port) in backend/.env and restart Compose.
- Mobile app can’t reach the backend: Use your computer’s LAN IP and ensure firewall allows inbound connections on chosen port; confirm you can hit http://YOUR_COMPUTER_LAN_IP from the phone’s browser.
- Android emulator networking: Use http://10.0.2.2 to reach the host machine.
- iOS simulator networking: Use http://127.0.0.1 (if backend runs on the same Mac) or the Mac’s LAN IP.
- Permission errors on storage or bootstrap/cache (e.g., "Operation not permitted" when running chmod/chown via Sail): The Compose files now mount these paths as named Docker volumes (laravel-storage, laravel-cache) so the container fully controls permissions. To apply on an existing setup, recreate the containers and volumes:
  - ./vendor/bin/sail down -v
  - ./vendor/bin/sail up -d
  After this, you should not need to run chmod/chown manually; Laravel can write logs, cache, and sessions.
- Docker Compose warnings about multiple config files: If you see messages like "Found multiple config files ... Using compose.yaml", it's because both backend/compose.yaml and backend/docker-compose.yml exist for compatibility. You can ignore the warning, or explicitly choose one with:
  - docker compose -f backend/compose.yaml up -d


---

Using Sail from the project root (quick start)
- Prerequisites: Docker Desktop running.
- From the repository root, use the Sail wrappers provided:
  - Start (build if needed): ./sail up -d
    - On PowerShell: .\sail.ps1 up -d
    - On CMD: .\sail.bat up -d
  - Stop: ./sail down
  - Rebuild images: ./sail build
  - View logs: ./sail logs -f
  - View specific service logs: ./sail logs -f laravel.test, ./sail logs -f frontend

Default ports
- Backend (Laravel): http://localhost:${APP_PORT:-8080}
- Frontend (Vite):   http://localhost:${FRONTEND_PORT:-5174}

To change ports
- Edit backend/.env (this file is also read by Docker Compose in backend/):
  - APP_PORT=8080        # host port for the Laravel service (maps to container port 80)
  - FRONTEND_PORT=5174   # host port for the Vite dev server container
- Then restart the stack:
  - ./sail down && ./sail up -d

Notes on API calls during development
- The frontend dev server proxies /api to the backend service inside the Docker network.
- By default, the proxy target is http://laravel.test (the Compose service name). This is set via VITE_PROXY_TARGET in backend/docker-compose.yml for the frontend container.
- When running the frontend outside Docker, set environment variable VITE_PROXY_TARGET to your backend origin (e.g., http://localhost:8080).



## Professional Docker Compose (ATTAQWA) — Critical Setup

Follow these once to use the optimized backend/docker-compose.yml with Laravel Sail, MTU fix, worker, and gateway integration.

Step A — Publish Sail Dockerfiles
- From the backend folder, publish Sail’s build context so backend/docker/8.5/ exists:
  - Linux/macOS: ./vendor/bin/sail artisan sail:publish
  - Windows (PowerShell): .\vendor\bin\sail artisan sail:publish

Step B — Prevent NPM build timeouts (slow networks)
- Edit backend/docker/8.5/Dockerfile and around the global npm install line, change to:
  - && npm config set fetch-retry-maxtimeout 600000 \
  - && npm install -g npm@latest \

Step C — Gateway network (development)
- No manual action needed. The backend/docker-compose.yml defines an internal bridge network named gateway-network that Compose creates automatically for service-to-service communication during development.

Start/Stop
- From the repository root (wrappers):
  - Start: ./sail up -d   (PowerShell: .\sail.ps1 up -d)
  - Stop:  ./sail down
- Or from backend/ directly with Compose:
  - docker compose -f backend/docker-compose.yml build --no-cache
  - docker compose -f backend/docker-compose.yml up -d

Migrations
- ./vendor/bin/sail artisan migrate

Notes
- Healthchecks: MySQL and Redis have healthchecks; Laravel waits for DB before starting.
- MTU 1350: Set on the sail network to fix TLS “bad record mac” issues.
- Gateway: The laravel.test service also joins an internal gateway-network for isolated service routing during development (created automatically by Compose).
- Containers are named (attaqwa-app, attaqwa-worker, attaqwa-db, etc.) for easier routing.


## Note: Sail image fallback to avoid GHCR 403 errors

Some environments cannot pull from GitHub Container Registry (ghcr.io) anonymously and see errors like:
- failed to fetch anonymous token: 403 Forbidden (ghcr.io)

Default: The stack uses Laravel Sail’s image (built locally). If you hit GHCR 403 during build/pull, use the provided override file to switch to a public PHP+Nginx image without changing your main compose file.

Quick fallback (no GHCR required):
- docker compose -f backend/docker-compose.yml -f backend/docker-compose.ghcr-fallback.yml up -d

Details:
- The override sets:
  - laravel.test → image: webdevops/php-nginx:8.3 (disables build)
  - worker       → image: webdevops/php-nginx:8.3
- Nginx will serve Laravel from /var/www/html/public inside the container.

To go back to Sail:
- Stop and remove with volumes (optional): ./vendor/bin/sail down -v
- Then run plain Sail again: ./vendor/bin/sail up -d
- If GHCR is accessible, you can also rebuild: docker compose -f backend/docker-compose.yml build --no-cache
- For private GHCR, authenticate first: docker login ghcr.io (token with read:packages)



## 8) Production hardening checklist

Before going live, review and apply the following:

- Backend environment (.env)
  - APP_ENV=production and APP_DEBUG=false
  - APP_URL=https://YOUR_PUBLIC_DOMAIN (use HTTPS in production)
  - Configure database credentials with strong passwords and non-root users
  - Set CORS_ALLOWED_ORIGINS to your real frontend origins (comma-separated). Do not use '*'.
  - If you use cookie auth (not typical here), set CORS_SUPPORTS_CREDENTIALS=true and configure SESSION_DOMAIN/SANCTUM_STATEFUL_DOMAINS accordingly.
  - Configure INACTIVITY_TIMEOUT_SECONDS to your desired idle timeout for API tokens (default 120s).
  - Rotate and set provider/API secrets (e.g., PAYSTACK_*, VTPASS_*). Do NOT commit real secrets to version control. Prefer Docker/host secret stores or CI secrets.
- Security headers
  - SecurityHeaders middleware is registered for both 'web' and 'api' groups. It adds X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, and HSTS (on HTTPS requests).
  - If a reverse proxy already sets these headers, the middleware will not override them.
- Rate limiting and brute-force protection
  - Global API limiter: 60 req/min per user or IP. Login endpoints: 10 req/min per IP. Adjust in App\\Providers\\AppServiceProvider::boot().
  - Public endpoints like /api/branches are under the 'api' throttle; /api/login uses the stricter 'login' throttle.
- Token inactivity timeout
  - Middleware InactivityTimeout revokes Sanctum personal access tokens idle longer than INACTIVITY_TIMEOUT_SECONDS (env). Default 120 seconds for development convenience — increase for production (e.g., 3600).
- Frontend configuration
  - For production/mobile builds, set frontend VITE_API_URL to the backend origin (no trailing slash).
  - Optional: Set VITE_HTTP_TIMEOUT (milliseconds) to control axios timeout. Default is 15000.
- HTTPS and reverse proxy
  - Terminate TLS at a reverse proxy (Traefik, Caddy, Nginx, cloud LB). Ensure proxy forwards X-Forwarded-* headers. Laravel is configured to trust proxies (bootstrap/app.php) so scheme/host are honored.
  - With HTTPS, HSTS is automatically added by SecurityHeaders.
- Observability and jobs
  - Run queue workers under a supervisor (e.g., Laravel Horizon, systemd, or Docker healthchecks) in production for reliability.
- Backups and migrations
  - Automate database backups and verify restore procedures. Ensure migrations run automatically (CI/CD) or during deploys.

