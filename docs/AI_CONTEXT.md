# AI Context

This file is a short working brief for future AI/code passes in this repo.

## Project Snapshot

- Laravel app for an education marketplace / LMS called `revisionhub`.
- Main user roles in the app:
  - `admin`
  - `instructor`
  - `student`
- The codebase currently has a lot of in-flight changes, so be careful not to assume untouched files are stable.

## Current In-Flight Work

- Admin authentication has been changed to OTP-only.
- Admin password-reset views/routes/controllers were removed.
- Admin login now sends users through OTP verification before session login.
- There was a previous 500 error on admin OTP verify caused by remember-token login behavior; the fix was to log the admin in without "remember" on the admin guard.

## Bunny Integration Context

- The admin settings page at `/admin/crediential-setting` is being updated for Bunny Cloud services.
- We are using three Bunny products:
  - CDN
  - Storage
  - Stream
- Bunny auth details matter:
  - Bunny Core API uses the account API key in the `AccessKey` header.
  - Bunny Storage uses the storage zone password in the `AccessKey` header.
  - Bunny Stream uses the library API key in the `AccessKey` header.
- The settings UI and config now include separate fields for:
  - Bunny core API key
  - Bunny CDN pull zone name and hostname
  - Bunny Storage zone name, access key, API endpoint, and CDN URL
  - Bunny Stream library ID, API key, CDN hostname, and pull zone name
- There is now a dedicated Bunny usage dashboard under the admin settings area to show CDN, Storage, Stream usage, and pending billing requests.
- The Bunny dashboard now also includes a CDN usage chart and backend invoice download links for pending payment requests.
- Bunny settings now include per-API connection test buttons for Core, CDN, Storage, and Stream, with status badges stored in session after each test.
- Bunny connection test results are now persisted in the `settings` table as JSON so the last known status survives logout/login.
- There is now a Bunny "Clear test history" action that resets the persisted test results back to empty.

## Code Areas To Check First

- `app/Http/Controllers/Admin/Auth/`
- `app/Http/Middleware/DemoModeMiddleware.php`
- `app/Helpers/helper.php`
- `app/Providers/SettingServiceProvider.php`
- `Modules/GlobalSetting/app/Http/Controllers/GlobalSettingController.php`
- `Modules/GlobalSetting/resources/views/credientials/`
- `config/bunny.php`
- `routes/admin.php`

## Working Notes

- Prefer small, targeted changes because many files already have unrelated edits.
- Do not revert user changes unless explicitly asked.
- If a bug touches admin auth, confirm the admin guard, OTP flow, and route redirects together.
- If a bug touches Bunny, check both the settings form and the config cache wiring.

