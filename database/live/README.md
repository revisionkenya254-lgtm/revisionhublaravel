# Live database import

`revisionhub_live_2026-10-18.sql` is a MySQL 8-compatible snapshot containing the
current schema and application data. It is intended for a fresh/empty live
database. Importing it into an existing database will drop and recreate tables.

SHA-256: `C37BDB86E92FF238C771EE0A2C7F4B1E3B7B4DAF722DA3BEAF7DBEE0EE0466C9`

Before importing, create an empty database using `utf8mb4`. Then import with
phpMyAdmin or:

```sh
mysql --default-character-set=utf8mb4 -u YOUR_USER -p YOUR_DATABASE < revisionhub_live_2026-10-18.sql
```

After import:

1. Configure the production `.env` file.
2. Re-enter mail, OAuth, payment, Bunny, AWS/Wasabi, Twilio, Zoom, and Jitsi
   credentials in the live environment/admin panel. Secrets and active tokens
   are intentionally excluded from this file.
3. Run `php artisan optimize:clear`, then `php artisan optimize`.
4. Ensure `storage` and `bootstrap/cache` are writable and deploy the matching
   `public/uploads` files referenced by the database.

The existing administrator password hash is preserved. Change the administrator
password immediately after the first live login.

`sanitize_live_export.sql` documents the sanitization applied to a temporary
copy during export. Do not run it against the source database.
