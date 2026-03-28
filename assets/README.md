App icon and splash assets

Place the following high‑resolution images in this folder so Capacitor can generate all required platform sizes:

- icon.png — 1024x1024 PNG without rounded corners or transparency effects required by platform guidelines.
- splash.png — 2732x2732 PNG. Keep important content centered; the edges may be cropped depending on device aspect ratio.

No design yet? Generate compliant placeholders
- From the project root, run: npm run assets:placeholders
- This will create opaque PNGs with the correct sizes (no rounded corners, no transparency) in assets/.
- If files already exist, the generator will skip them.

Branded ATTAQWA design assets (auto‑generated)
- From the project root, run: npm run cap:assets
- This runs a pre-step that overwrites assets/icon.png and assets/splash.png with branded versions based on BRAND_SLUG/VITE_BRAND_SLUG.
- With the default configuration (frontend/.env sets VITE_BRAND_SLUG=attaqwa), the icon has a white badge with the ATTAQWA wordmark and the splash shows centered ATTAQWA text on green.
- Then proceed to sync platforms.

How to generate platform assets (after placing the images or generating placeholders):

1) Install dependencies (from project root):
   npm install

2) Generate all icons and splash screens:
   npm run cap:assets

3) Sync platforms:
   npm run cap:sync

Notes
- Android and iOS platforms for this project live under frontend/android and frontend/ios. The Capacitor configuration is set accordingly so the generator places files in the right locations.
- If you want to generate for a single platform:
   npm run cap:assets:android
   npm run cap:assets:ios
