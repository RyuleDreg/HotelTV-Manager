# Upload and build from an Android phone

## Upload this repository package

1. Extract the ZIP on your phone.
2. Open `RyuleDreg/HotelTV-Manager` on GitHub.
3. Tap **Add file** and then **Upload files**.
4. Upload all files and folders inside the extracted package. Upload the contents, not the outer folder itself.
5. Commit directly to `main`.

GitHub will start two Actions automatically:

- **Build HotelTV APK**
- **Package HotelTV Backend**

## Download the APK

1. Open the repository's **Actions** tab.
2. Open the newest **Build HotelTV APK** run.
3. Wait for a green check mark.
4. Scroll to **Artifacts**.
5. Download `HotelTV-0.1.0-alpha1-APK`.
6. Extract it to obtain `HotelTV-0.1.0-alpha1.apk`.
7. Sideload the APK onto the Android TV device.

## If GitHub reports a failed build

Send the failed Actions screenshot or job log in ChatGPT. The workflow and source can then be corrected without Android Studio.
