# Mobile App Versioning & Forced Updates

This guide explains how to properly version the mobile app and how to use the "Forced Update" system to ensure all members are using a secure and Sharia-compliant version of the app.

## 1. Where to update version numbers

When preparing a new release, you must update the version in **three** places:

### A. Frontend environment (`frontend/.env`)
Update `VITE_APP_VERSION`. This version is used by the app to check against the backend for updates.
```env
VITE_APP_VERSION=1.0.1
```

### B. Android Project (`frontend/android/app/build.gradle`)
Update both `versionCode` and `versionName`.
- `versionCode`: An integer that must increase with every Play Store upload (e.g., `1` -> `2`).
- `versionName`: The user-visible version string (e.g., `"1.0"` -> `"1.1"`).

```gradle
defaultConfig {
    ...
    versionCode 2
    versionName "1.1"
}
```

### C. iOS Project (Xcode)
If using iOS, open the project in Xcode (`npm run cap:open:ios`) and update:
- **Version**: Equivalent to `versionName` (e.g., `1.1`).
- **Build**: Equivalent to `versionCode` (e.g., `2`).

---

## 2. Triggering Updates from the Backend

Once your new version is live on the Play Store/App Store, you can control which versions are allowed to access the app via the Filament Admin panel.

### Step-by-Step:
1. **Login** to the Admin Panel.
2. Navigate to **App Status Settings** (under the System or Settings menu).
3. Update the following fields:

#### Forced (Hard) Update
- **Minimum Mobile Version**: Set this to the version you want to *require*. 
  - *Example*: If you set it to `1.1.0`, any user on `1.0.9` or lower will be blocked by the "Update Required" screen and sent to the store.
  - *Use for*: Critical security patches, breaking API changes, or Sharia logic corrections.

#### Recommended (Soft) Update
- **Current Mobile Version**: Set this to the latest version available in the store.
  - *Example*: If you set it to `1.2.0`, users on `1.1.0` will see a non-blocking "New Version Available" banner on their dashboard.
  - *Use for*: New features or non-critical improvements.

### Fallback (.env)
If the database settings are empty, the backend falls back to these variables in `backend/.env`:
```env
MOBILE_MIN_VERSION=1.0.0
MOBILE_CURRENT_VERSION=1.0.0
```

---

## 3. Best Practices for Versioning

1. **Follow Semantic Versioning (SemVer)**:
   - `1.0.0` (Major.Minor.Patch)
   - Patch: Bug fixes.
   - Minor: New features.
   - Major: Breaking changes.
2. **Always test before forcing**: Ensure the version you are setting as the "Minimum" is actually approved and available for download in the Play Store, otherwise users will be stuck in a loop.
3. **Use the "Soft Update" first**: For most updates, update the "Current Version" first. Wait a few days for users to update voluntarily before increasing the "Minimum Version" to force the rest.
