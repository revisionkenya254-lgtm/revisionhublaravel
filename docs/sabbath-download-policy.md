# Sabbath download policy

Android download responses expose a machine-readable retention policy. A file
granted only by the Sabbath plan is temporary and must be deleted on or after
`delete_after`.

```json
{
  "sabbath_download": true,
  "sabbath_download_tag": "encrypted-and-tamper-evident-token",
  "delete_after": "2026-10-24T16:00:00+00:00",
  "download_policy": {
    "type": "sabbath_temporary",
    "temporary": true,
    "sabbath_download": true,
    "tag": "encrypted-and-tamper-evident-token",
    "delete_after": "2026-10-24T16:00:00+00:00"
  }
}
```

The same values are returned on protected file responses as:

- `X-RevisionHub-Download-Policy: sabbath-temporary`
- `X-RevisionHub-Sabbath-Tag: <token>`
- `X-RevisionHub-Delete-After: <ISO-8601 timestamp>`

For a paid subscription, owned product, or enrolled course, the policy is
permanent: `sabbath_download` is `false`, and both the tag and deletion time are
`null`.

## Android behavior

1. Persist the complete `download_policy` beside the local file.
2. Schedule deletion at `delete_after`; also reconcile on app start and login.
3. Delete only when `temporary` is `true` and the stored tag is present.
4. Treat timestamps as ISO-8601 instants rather than device-local wall time.
5. Do not let a later Sabbath download replace a permanent entitlement record
   for the same file.

The token is encrypted and authenticated by the API. It is opaque to clients;
Android should store it unchanged and must not attempt to decode it.
