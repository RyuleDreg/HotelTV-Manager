# HotelTV Manager for Android TV

Initial Android TV administration client for the HotelTV Manager platform.

## Included in this first scaffold

- Kotlin and Jetpack Compose for TV
- Android TV launcher integration and landscape layout
- Hosted server or local `.hoteltv-manager.db` configuration
- Username/password entry with saved configuration
- TV remote-friendly dashboard cards
- Property, room, device, playlist, messaging and system-status placeholders
- GitHub Actions workflow that builds a downloadable debug APK

## Build the APK in GitHub

1. Upload this project to the root of `RyuleDreg/HotelTV-Manager`.
2. Open the repository's **Actions** tab.
3. Select **Build Android APK** and press **Run workflow**, or simply push a commit.
4. Open the completed run and download **HotelTV-Manager-debug-apk** under Artifacts.
5. Extract the ZIP to obtain `app-debug.apk`.

## Local build

Install JDK 17 and Gradle 8.13, then run:

```bash
gradle assembleDebug
```

The APK will be created at `app/build/outputs/apk/debug/app-debug.apk`.

## Next development stage

Connect the login screen to the HotelTV Manager backend, implement the local SQLite database provider, and replace dashboard placeholders with property/room/device CRUD screens.
