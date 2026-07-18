# Backend deployment

The server layout is:

```
/home/account/
├── hoteltv/
└── public_html/
```

Upload `backend/hoteltv` outside the public web root and merge `backend/public_html` into the site's public directory.

Before upgrading, back up:

- `hoteltv/config`
- `hoteltv/storage/database`
- `public_html`

Do not delete the existing database and do not rerun the installer on an installed system.
