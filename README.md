# HotelTV Manager

Self-hosted hotel IPTV management platform.

## Version

`0.1.0-alpha1`

## Included

- PHP 7.2-compatible HotelTV Manager backend
- Properties, floors, rooms, devices, IPTV accounts and playlist profiles
- Device activation, heartbeat and configuration API foundations
- Android TV application with two setup options:
  - Hotel Manager activation code
  - Direct Xtream Codes username/password login
- Automatic GitHub Actions APK build
- Automatic backend deployment ZIP build

## Repository layout

- `backend/hoteltv` — private application files, placed outside the public web root
- `backend/public_html` — public web files
- `android-tv` — Android TV application
- `.github/workflows` — automatic APK and backend builds
- `docs` — installation and release documentation

## APK build

Every push affecting `android-tv` automatically runs **Build HotelTV APK**. The finished artifact is named:

`HotelTV-0.1.0-alpha1-APK`

Inside it is:

`HotelTV-0.1.0-alpha1.apk`

See `docs/PHONE-UPLOAD-AND-BUILD.md`.
