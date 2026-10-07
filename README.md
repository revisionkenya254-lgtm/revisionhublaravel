# RevisionHubKenya

RevisionHubKenya is a learning, revision-resource, and exam-preparation platform for learners in Kenya.

## Application setup

Configure the environment, install dependencies, and run the database migrations:

```bash
composer install
php artisan migrate
php artisan optimize:clear
```

## Account deletion

The public account-deletion request page is available at `/delete-account` and can be submitted as the web deletion URL in Google Play Console. Users request deletion with their account email, confirm through a signed link sent to that address (valid for 60 minutes), then confirm the irreversible deletion in the browser. A user-facing request action is also present in student and instructor settings, and mobile clients can request the email confirmation through `DELETE /api/auth/account`.

Student accounts and their account-owned records are deleted, including assignment submission files, profile images, and active API/device sessions. Instructor profiles are anonymized and disabled so published courses, products, and related student learning records can remain available. Personal buyer/order details are cleared while order rows remain for accounting purposes. Review the applicable legal and financial retention requirements and keep the Play Console Data safety disclosures aligned with production behavior.
