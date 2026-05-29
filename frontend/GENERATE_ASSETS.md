Generate Capacitor icons and splash screens (from the frontend folder)

This project keeps the native platforms under frontend/android and frontend/ios, but the npm scripts that generate icons and splash screens live in the repository root package.json.

If you are currently inside the frontend directory, use one of the following approaches to run the generator.

Quick commands (run while in frontend/)
- Install root dependencies: npm --prefix .. install
- Generate branded 'ASSALAM' placeholders: npm --prefix .. run assets:brand
- Generate all icons and splash screens: npm --prefix .. run cap:assets
- Generate Android only: npm --prefix .. run cap:assets:android
- Generate iOS only: npm --prefix .. run cap:assets:ios
- Sync platforms: npm --prefix .. run cap:sync

Alternative: temporarily go to the repo root
- cd ..
- npm install
- npm run cap:assets
- npm run cap:sync
- cd frontend

Prerequisites
1) Provide source images at the repo root in assets/:
   - assets/icon.png â€” 1024x1024 PNG, no rounded corners, no transparency.
   - assets/splash.png â€” 2732x2732 PNG, keep important content centered.

2) No design yet? You can generate compliant placeholders from the frontend folder:
   - npm --prefix .. run assets:placeholders
   This creates assets/icon.png and assets/splash.png at the repo root.

What the generator does
- Uses @capacitor/assets to create all required sizes for Android and iOS.
- Because capacitor.config.json sets android.path to "frontend/android" and ios.path to "frontend/ios", the generated images are placed directly into those platform folders.

Troubleshooting
- If npm --prefix .. install fails due to network hiccups, try again or run from the repo root with npm install.
- Ensure you have Node.js and npm installed.

Reference scripts (defined in repo-root package.json)
- cap:assets â†’ npx @capacitor/assets generate
- cap:assets:android â†’ npx @capacitor/assets generate --platform android
- cap:assets:ios â†’ npx @capacitor/assets generate --platform ios
- cap:sync â†’ npx cap sync
- assets:placeholders â†’ node scripts/generate-placeholder-assets.mjs
