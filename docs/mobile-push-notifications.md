# Mobile push notifications

## Server setup

1. Enable Firebase Cloud Messaging for the Firebase project used by the Android app.
2. Create a Firebase service account with permission to send FCM messages. Store its JSON key outside the public web root with read access limited to the application user; never commit or upload the key to a public folder.
3. Configure `FIREBASE_PROJECT_ID` and `FIREBASE_CREDENTIALS` in the production environment. `FIREBASE_CREDENTIALS` must be the absolute path to the service-account JSON file.
4. Run `php artisan migrate --force`.
5. Run a Laravel queue worker that processes the `default` queue. Push preparation and sends are queued in batches; without a running worker, campaigns remain queued.
6. Grant the admin role the existing `customer.view` permission to access Push Notifications.

## Android device registration API

The Android app should request notification permission before registering its FCM token. When permission is granted and a student is authenticated, register or refresh the current token:

```http
POST /api/push/devices
Authorization: Bearer <access_token>
Accept: application/json
Content-Type: application/json

{
  "token": "<current-fcm-registration-token>",
  "platform": "android"
}
```

Unregister the token when the user disables push notifications or signs out:

```http
DELETE /api/push/devices
Authorization: Bearer <access_token>
Accept: application/json
Content-Type: application/json

{
  "token": "<current-fcm-registration-token>"
}
```

Refresh server registration whenever Firebase rotates the token. Do not register devices before permission has been granted. Android remains the final authority for whether notifications are displayed if system permission is later revoked.

## Admin campaigns

Admins with `customer.view` can create campaigns in the admin sidebar under **Manage Users → Push Notifications**. Campaigns target active, unbanned student accounts that have registered an Android token. An education-level target includes only students whose saved `education_level` matches; accounts without a level are excluded.

Campaigns are sent immediately. The campaign history tracks recipients, successful sends, failures, and status. FCM acceptance means the message was accepted by Firebase, not that it was displayed or opened on the device.

## Recommended notification use cases and guardrails

- **Time-sensitive service announcements:** planned maintenance, service outages, or important policy changes. Keep these infrequent and factual.
- **Relevant study materials:** notify a learner when new notes, past papers, quizzes, or courses match their education level or explicitly followed subjects.
- **Revision reminders:** optional reminders tied to a learner-selected exam date, study plan, or unfinished learning activity. Let learners control reminder frequency and timing.
- **Account and payment events:** use push for useful status changes such as a completed purchase or expiring subscription, without putting private or payment details in the lock-screen text.
- **Milestones:** celebrate meaningful progress (for example, finishing a course) only when it is timely and useful to the learner.

Prefer event-triggered and audience-relevant messages over generic broadcasts. Use an accurate deep link, cap frequency per user, honor quiet hours and in-app notification settings, avoid duplicate sends, and measure opt-outs as well as sends and opens. Do not send sensitive personal, academic, or payment information in notification text. For level-based content, verify that the material really matches that level and exclude users whose level is unknown.
