# Queue Production Setup

This project now supports two document-processing modes:

- `local`: runs through the standard Laravel database queue.
- `local`: processes document jobs immediately in the request.
- `redis`: routes AI document jobs to Redis so Horizon can manage them.

## Shared hosting

If you are starting on shared hosting, use this path first:

```env
QUEUE_CONNECTION=database
AI_DOCUMENT_QUEUE_MODE=local
AI_DOCUMENT_QUEUE_NAME=ai-processing
AI_DOCUMENT_LOCAL_CONNECTION=database
```

Add two cron jobs from [deploy/shared-hosting/cron-jobs.txt](./shared-hosting/cron-jobs.txt):

- `schedule:run` every minute
- `queue:work --stop-when-empty` every minute

This keeps the processing asynchronous without needing Supervisor or Horizon.

## Local mode

Use this on simpler servers or while you want processing to happen immediately on click:

```bash
php artisan queue:work --queue=ai-processing,default --sleep=1 --tries=3 --timeout=180
```

## Redis / Horizon mode

Use this when Redis and Horizon are available on the production server:

```bash
php artisan horizon
```

Recommended Supervisor process for the AI document queue:

- [deploy/supervisor/ai-document-processing.conf](./supervisor/ai-document-processing.conf)

Recommended systemd service alternative:

- [deploy/systemd/revisionhub-ai-document-processing.service](./systemd/revisionhub-ai-document-processing.service)

## Important

Do not rely on the Laravel scheduler to start the worker. For local/immediate mode, no worker is needed for AI document processing. On VPS/dedicated servers with Redis, prefer Supervisor or systemd.

### Environment variables

```env
QUEUE_CONNECTION=database
AI_DOCUMENT_QUEUE_MODE=local
AI_DOCUMENT_QUEUE_NAME=ai-processing
AI_DOCUMENT_LOCAL_CONNECTION=database
AI_DOCUMENT_REDIS_CONNECTION=redis
```

Switch `AI_DOCUMENT_QUEUE_MODE=redis` once the Redis queue and Horizon are ready.
