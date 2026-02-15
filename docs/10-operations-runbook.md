# 10. Operations Runbook

## Required Environment Configuration

- `APP_URL`
- `DB_*`
- `QUEUE_CONNECTION`
- `MAIL_*`
- `STRIPE_KEY`
- `STRIPE_SECRET`
- `STRIPE_WEBHOOK_SECRET`
- `CF_STREAM_IFRAME_BASE_URL`
- `CF_STREAM_SIGNED_URLS_ENABLED` (optional hardening)
- `CF_STREAM_TOKEN_TTL_SECONDS` (if signed URLs enabled)
- `CF_STREAM_ACCOUNT_ID` (if signed URLs enabled)
- `CF_STREAM_API_TOKEN` (if signed URLs enabled)
- `CF_STREAM_CUSTOMER_CODE` (if signed URLs enabled)
- `COURSE_RESOURCES_DISK`
- `AWS_*` (for Cloudflare R2 when `COURSE_RESOURCES_DISK=s3`)

## Pre-Launch Checks

1. Stripe products/prices configured and mapped.
2. Webhook endpoint reachable and verified.
3. Queue workers running.
4. Stream and R2 credentials validated.
5. Seed/imported course content is published and ordered.
6. Every purchasable course has a non-null `stripe_price_id`.

For exact setup steps, use `docs/19-stripe-cloudflare-setup.md`.

## Internal Operational Procedures

### Add/Update Course Content

- Use DB seeders or Artisan import command.
- Ensure module/lesson ordering and publish flags are correct.
- Verify `stream_video_id` and resource object keys exist.

### Reprocess Webhook Event

- Locate `stripe_event_id` in `stripe_events`.
- Run deterministic replay command:
  - `php artisan videocourses:stripe-reprocess {stripe_event_id}`

### Manual Entitlement Override

- Grant via command:
  - `php artisan videocourses:entitlement-grant {user_id} {course_id} {order_id}`
- Revoke via command:
  - `php artisan videocourses:entitlement-revoke {user_id} {course_id}`
- Record reason and operator identity in audit trail.

### Audit Log Review

- Query `audit_logs` for event inspection during incidents.
- Key event types:
  - `checkout_started`
  - `stripe_webhook_processed`
  - `stripe_event_reprocessed`
  - `entitlement_granted`
  - `entitlement_revoked`
  - `resource_download_requested`
  - `resource_stream_served`
  - `resource_download_denied`
  - `purchase_claim_completed`

## Common Incident Playbooks

### Payment succeeded in Stripe but no access

1. Check webhook delivery in Stripe dashboard.
2. Confirm event presence in `stripe_events`.
3. Reprocess event.
4. Validate order state and entitlement row.

### Webhook downtime

1. Restore endpoint availability.
2. Replay undelivered events from Stripe.
3. Verify idempotent processing.

### Download link expired complaint

1. Confirm entitlement is active.
2. Confirm resource exists in R2.
3. Trigger fresh signed URL via normal endpoint.
