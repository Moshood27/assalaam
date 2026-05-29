# Build and sync the mobile apps (Android & iOS)

This project uses Capacitor (v6) with a Vue frontend. Native platforms live under `frontend/android` and `frontend/ios`, while the Capacitor CLI and helper scripts are defined in the repository root `package.json`.

Important: Most Capacitor commands should be run from the repo root unless explicitly noted. When you are inside `frontend/`, you can still run the root scripts using the `npm --prefix ..` pattern (examples below).


## Project layout (relevant to build/sync)
- Repo root
  - capacitor.config.json → points to `webDir: frontend/dist` and platform paths
  - package.json → contains Capacitor scripts (cap:add, cap:sync, cap:open, cap:assets)
  - assets/ → source images for icons and splash (icon.png 1024×1024, splash.png 2732×2732)
- frontend/
  - package.json → Vite build scripts for the web app
  - dist/ → built web assets (created by `npm run build`)
  - android/ → Android project
  - ios/ → iOS project
  - public/manifest.webmanifest → PWA manifest used for generating PWA icons


## Prerequisites
- Node.js 18+ and npm installed.
- Android: Android Studio + SDK, Java 17. Ensure ANDROID_HOME is set (Android Studio manages this for you).
- iOS (macOS only): Xcode, CocoaPods, an Apple developer account for real device signing.
- If using WSL2: run Android Studio/Xcode on the host OS, not inside WSL. Build the web in WSL if you like, but open and run the native IDEs on Windows/macOS.


## One‑time setup (per machine)
From the repo root:
1) Install root dependencies
   - npm install
2) Install frontend dependencies
   - npm --prefix frontend install
3) Add platforms (only once per repo unless removed)
   - Android: npm run cap:add:android
   - iOS (macOS only): npm run cap:add:ios
4) Provide/generate app assets (icons & splash)
   - Place images at repo root: assets/icon.png (1024×1024), assets/splash.png (2732×2732)
   - Generate all platform assets from root: npm run cap:assets
   - If you’re in frontend/: npm --prefix .. run cap:assets

Note: There’s an additional guide focused on asset generation in `frontend/GENERATE_ASSETS.md`.


## Day‑to‑day workflow
You typically do this when you want to test new frontend code in the native apps.

Option A — Run everything from the repo root
1) Build the web app (outputs to frontend/dist)
   - npm --prefix frontend run build
2) Sync web assets and native projects
   - npm run cap:sync
3) Open the native project in the IDE
   - Android Studio: npm run cap:open:android
   - Xcode (macOS): npm run cap:open:ios
4) Build and run from the IDE
   - Use Android Studio’s Run/Debug, or Xcode’s build/run for simulators/devices.

Option B — If you are already inside the frontend/ folder
1) Build the web app
   - npm run build
2) Sync from the repo root using prefix
   - npm --prefix .. run cap:sync
3) Open the native project from the repo root using prefix
   - Android: npm --prefix .. run cap:open:android
   - iOS (macOS): npm --prefix .. run cap:open:ios


## Quick reference: scripts
(Defined in the repo root package.json)
- cap:add:android → npx cap add android
- cap:add:ios → npx cap add ios
- cap:sync → npx cap sync
- cap:open:android → npx cap open android
- cap:open:ios → npx cap open ios
- cap:assets → npx @capacitor/assets generate --androidProject frontend/android --iosProject frontend/ios/App --pwaManifestPath frontend/public/manifest.webmanifest
- cap:assets:android → npx @capacitor/assets generate --android --androidProject frontend/android
- cap:assets:ios → npx @capacitor/assets generate --ios --iosProject frontend/ios/App
- cap:assets:pwa → npx @capacitor/assets generate --pwa --pwaManifestPath frontend/public/manifest.webmanifest


## Typical full cycle (fresh changes)
From the repo root:
- npm --prefix frontend run build
- npm run cap:sync
- npm run cap:open:android  (or npm run cap:open:ios on macOS)
- Run from the IDE


## Troubleshooting
- cap:sync says “Unable to find node_modules/@capacitor/android”
  - Run npm install at the repo root. The Capacitor CLI and platform packages are installed there.
- Android builds but shows outdated UI
  - Ensure you rebuilt the web: npm --prefix frontend run build, then re-run npm run cap:sync.
- Assets (icons/splash) don’t appear updated
  - Re-run npm run cap:assets (from root). Make sure assets/icon.png and assets/splash.png exist and meet the size requirements.
- iOS build errors on non‑macOS
  - iOS builds require macOS with Xcode. Use Android on Windows/Linux.


## Notes on paths
The repo’s capacitor.config.json sets:
- webDir: frontend/dist → cap sync copies this into the native projects
- android.path: frontend/android
- ios.path: frontend/ios

This is why you run the Capacitor CLI from the repo root. When inside `frontend/`, prefer the `npm --prefix ..` pattern to call the root scripts without changing directories.


## Android: clean cache and build directly from the platform folder
Use these steps when you want to clean Gradle caches, re-sync Capacitor, and build a fresh debug APK.

1) Go to the Android folder
- cd frontend/android
  - Full path example: cd ~/development/cooperative/frontend/android

2) Remove the old Gradle cache and build outputs
- Linux/macOS (bash): rm -rf .gradle build app/build
- Windows (PowerShell): Remove-Item -Recurse -Force .gradle, build, app/build

3) Go back to the repo root and sync Capacitor again (regenerates capacitor.settings.gradle)
- cd ..
- npx cap sync android

4) Go back to android and build a debug APK
- cd android
- Linux/macOS: ./gradlew assembleDebug
- Windows (PowerShell): .\gradlew.bat assembleDebug

5) Find the output APK here
- frontend/android/app/build/outputs/apk/debug/app-debug.apk

Notes
- If you get a “Permission denied” on ./gradlew, run: chmod +x gradlew
- Ensure Java 17 and Android SDK are installed and available to Gradle (Android Studio handles this).


## AS-SALAAM splash and app icon (quick steps)
The project now generates and uses AS-SALAAM-branded splash and icons by default.

- To regenerate the AS-SALAAM splash/logo and sync them into Android and iOS projects in one step:

  npm run mobile:assets:sync

  This runs the following under the hood:
  - Pre-step: node scripts/generate-branded-assets.mjs --force (creates assets/icon.png and assets/splash.png with AS-SALAAM wordmark and brand color)
  - Generate sizes: npx @capacitor/assets generate (writes platform-specific resources under frontend/android and frontend/ios)
  - Sync: npx cap sync (copies web and refreshed assets into native projects)

- To generate ASSALAM-branded splash/logo and sync them into Android and iOS in one step:

  npm run mobile:assets:sync:assalam

  You can also only generate the images without syncing:

  npm run assets:brand:assalam

Notes
- The brand is controlled by BRAND_SLUG/VITE_BRAND_SLUG env vars. The default in frontend/.env is assalaam, so you don’t need to set anything for AS-SALAAM.
- Alternatively, you can override via CLI: node scripts/generate-branded-assets.mjs --force --brand assalam
- The mobile app hides the splash screen on startup (see frontend/src/App.vue using SplashScreen.hide()), so the splash is connected to the app lifecycle.
- If you only target one platform:
  - Android: npm run mobile:assets:android
  - Android (ASSALAM): npm run mobile:assets:android:assalam
  - iOS (macOS): npm run mobile:assets:ios
  - iOS (ASSALAM, macOS): npm run mobile:assets:ios:assalam
