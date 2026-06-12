---
name: build-apk
description: Build the Android APK for Asistencia Vircom with correct logo, versioning, and deploy it for download. Use when the user says "haz la apk", "build apk", "genera apk", "actualiza apk", or "nueva version apk".
---

# Build Android APK

Use ONLY when the user asks to build/generate/update the Android APK.

## Steps

### 1. Check version
```bash
cd /home/vircom/.gemini/antigravity/scratch/ionic-frontend
```

### 2. Bump version
```bash
node version-bump.cjs
```
This increments the version in `package.json` and `android/app/build.gradle`.

### 3. Update logo (if needed)
If user provides a new logo, place it at `resources/vircom/icon.png` (minimum 1024x1024, RGBA PNG).

### 4. Generate Android icons from source
```bash
cd /home/vircom/.gemini/antigravity/scratch/ionic-frontend
npx @capacitor/assets generate --android \
  --iconBackgroundColor '#FFFFFF' \
  --iconBackgroundColorDark '#0f172a' \
  --splashBackgroundColor '#FFFFFF'
```

### 5. Sync Capacitor web assets
```bash
npx cap sync android
```

### 6. Build APK
```bash
export JAVA_HOME="/home/vircom/.gemini/antigravity/scratch/jdk-21.0.2"
export PATH="$JAVA_HOME/bin:$PATH"
export ANDROID_HOME="/home/vircom/.gemini/antigravity/scratch/android-sdk"
cd /home/vircom/.gemini/antigravity/scratch/ionic-frontend/android
./gradlew assembleDebug
```

### 7. Copy APK with version
```bash
# Read new version from build.gradle
VERSION=$(grep 'versionName' /home/vircom/.gemini/antigravity/scratch/ionic-frontend/android/app/build.gradle | sed 's/.*"\(.*\)".*/\1/')
echo "Version: $VERSION"

# Copy to ionic-frontend dir
cp android/app/build/outputs/apk/debug/app-debug.apk ../vircom-debug.apk

# Copy to web public dir
cp ../vircom-debug.apk /home/vircom/.gemini/antigravity/scratch/asistenciavircom/public/app.apk

# Update version file for the download route
echo "{\"version\": \"$VERSION\", \"buildDate\": \"$(date +%Y-%m-%d)\"}" > /home/vircom/.gemini/antigravity/scratch/asistenciavircom/public/app-version.json
```

### 8. Deploy APK to VPS
```bash
# Copy version file and APK
cat /home/vircom/.gemini/antigravity/scratch/asistenciavircom/public/app.apk | \
  ssh -o StrictHostKeyChecking=no root@191.101.233.82 \
  "cat > /tmp/app.apk && docker cp /tmp/app.apk asistencia-app:/var/www/public/app.apk"
cat /home/vircom/.gemini/antigravity/scratch/asistenciavircom/public/app-version.json | \
  ssh -o StrictHostKeyChecking=no root@191.101.233.82 \
  "cat > /tmp/app-version.json && docker cp /tmp/app-version.json asistencia-app:/var/www/public/app-version.json"
```

### 9. Verify
```bash
# Check version in filename
curl -sI https://asistenciavircom.com/descargar-app | grep -i filename
```

## Notes
- Logo file must be at least 1024x1024
- Download URL is always `https://asistenciavircom.com/descargar-app`
- The route `/descargar-app` serves `public/app.apk` via Laravel
- JDK 21 is at `/home/vircom/.gemini/antigravity/scratch/jdk-21.0.2`
- Android SDK is at `/home/vircom/.gemini/antigravity/scratch/android-sdk`
- Ionic frontend at `/home/vircom/.gemini/antigravity/scratch/ionic-frontend`
