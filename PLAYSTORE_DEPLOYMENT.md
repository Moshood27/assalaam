# Google Play Store Deployment Process

This guide explains how to build, sign, and upload the Android version of the app to the Google Play Store.

## 1. Prerequisites
- A **Google Play Console** developer account.
- **Android Studio** (for building and signing).
- **Keystore file** (for signing the release build).
- The `google-services.json` file for push notifications (if not already in the project).

---

## 2. Preparing the Build
Before generating the final file, ensure you have:
1. Updated the version numbers (see `MOBILE_VERSIONING.md`).
2. Configured the correct production API URL.

### Step-by-Step Build Commands:
From the repository root:
1. **Build the Frontend (for mobile)**:
   ```bash
   # Set the production API URL (no trailing slash)
   $env:VITE_API_URL="https://api.yourcoop.org"; npm run build --prefix frontend
   ```
2. **Sync with Capacitor**:
   ```bash
   npm run cap:sync
   ```
3. **Open Android Studio**:
   ```bash
   npm run cap:open:android
   ```

---

## 3. Generating the Signed Android App Bundle (AAB)
The Google Play Store requires an **Android App Bundle (.aab)** for all new apps and updates.

1. In Android Studio, go to: **Build > Generate Signed Bundle / APK...**
2. Select **Android App Bundle** and click **Next**.
3. **Keystore Selection**:
   - Choose your existing `.jks` file.
   - Enter the keystore and key passwords.
   - Enter the key alias.
   - *Note*: If you don't have a keystore, click "Create new..." and store it safely. **Losing this file means you can never update your app again.**
4. Select **release** build variant.
5. Choose the **Destination Folder** for the generated `.aab`.
6. Click **Finish**.

---

## 4. Uploading to Google Play Console

1. Log in to your [Google Play Console](https://play.google.com/console/).
2. Select the **assalaam** app.
3. In the sidebar, go to **Testing > Production** (or Internal Testing for testing builds).
4. Click **Create new release**.
5. Upload the `.aab` file you generated.
6. **Release Notes**: Write what's new in this version.
7. Click **Save** and then **Review release**.
8. Click **Start rollout to Production**.

---

## 5. Post-Release: The "Forced Update"
Once Google approves your release (usually takes a few hours to a few days):
1. **Verify** the update is live in the Play Store app.
2. Go to the **Filament Admin Panel** and update the `Minimum Mobile Version` as explained in `MOBILE_VERSIONING.md`.

---

## 6. Common Issues & Solutions

### A. Signing Key Mismatch
If you get an error that the signing key is different from the one in the store, it means you're using the wrong keystore file. Locate the original one used for the first upload.

### B. Version Code Conflict
Google will reject your upload if the `versionCode` in `build.gradle` is the same as or lower than the one already in the store. Always increment `versionCode` by 1.

### C. Large App Size
Ensure you use the **Android App Bundle (.aab)** format, which optimizes the download size for users based on their device configuration.
