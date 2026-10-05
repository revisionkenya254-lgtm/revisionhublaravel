# Shared Hosting Queue Setup

This folder is for Linux shared hosting environments where you usually do not have Supervisor, systemd, or Horizon.

Use the database queue for the first deployment:

- `QUEUE_CONNECTION=database`
- `AI_DOCUMENT_QUEUE_MODE=local`

In local mode, AI document processing happens immediately on click.

Then add these cron jobs if you still want the rest of the app's scheduled jobs to run:

1. Run Laravel scheduler every minute.
2. Run the document queue worker every minute only if you have other queued jobs that need it.

If your host gives you a different PHP binary path, replace `/usr/bin/php` with the path they provide.
