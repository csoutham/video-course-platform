# 07. Video and Resource Delivery

## Video: Cloudflare Stream

### Lesson Video Model

Each lesson stores `stream_video_id` for playback lookup.

Playback URL resolution:

- Default iframe base: `https://iframe.videodelivery.net`
- Config override via `CF_STREAM_IFRAME_BASE_URL`

### Playback Access Rules

1. User requests lesson player.
2. App validates active entitlement.
3. App renders player context only for authorized users.
4. Unentitled users are blocked before video context is emitted.

### Optional Hardening: Signed Stream URLs

Use signed Stream URLs for production hardening.

Required env when enabled:

- `CF_STREAM_SIGNED_URLS_ENABLED=true`
- `CF_STREAM_ACCOUNT_ID`
- `CF_STREAM_API_TOKEN`
- `CF_STREAM_CUSTOMER_CODE`
- `CF_STREAM_TOKEN_TTL_SECONDS` (recommended short TTL)

Implementation behavior:

1. App requests a signed Stream token from Cloudflare API per playback request.
2. App renders tokenized embed URL at `https://customer-{code}.cloudflarestream.com/{token}/iframe`.
3. Missing/invalid signing config fails closed.

### Operational Notes

- Keep unpublished lessons hidden from customer routes.
- Track unavailable/missing Stream IDs as content integrity errors.

## Resources: Cloudflare R2 Signed URLs

### Storage Rules

- Use private bucket only.
- Store object key in `lesson_resources.storage_key`.

### Download Flow

1. User hits `/resources/{resource}/download`.
2. App validates authentication and entitlement via resource->lesson->course chain.
3. App generates short-lived signed URL.
4. App redirects client to signed URL.

### Security Defaults

- Short URL expiry (for example: 1-5 minutes).
- No public bucket/object ACLs.
- Optional download access logs for support and audits.
