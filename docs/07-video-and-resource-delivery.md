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
