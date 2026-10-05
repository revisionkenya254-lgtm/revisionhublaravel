#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../../"
/usr/bin/php artisan queue:work --queue=ai-processing,default --sleep=1 --tries=3 --stop-when-empty
