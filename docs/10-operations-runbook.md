# 10. Operations Runbook

## Required Environment Configuration

- `APP_URL`
- `DB_*`
- `QUEUE_CONNECTION`
- `MAIL_*`
- `STRIPE_KEY`
- `STRIPE_SECRET`
- `STRIPE_WEBHOOK_SECRET`
- `CF_STREAM_*`
- `R2_*`

## Pre-Launch Checks

1. Stripe products/prices configured and mapped.
2. Webhook endpoint reachable and verified.
3. Queue workers running.
4. Stream and R2 credentials validated.
5. Seed/imported course content is published and ordered.

## Internal Operational Procedures

### Add/Update Course Content

- Use DB seeders or Artisan import command.
- Ensure module/lesson ordering and publish flags are correct.
- Verify `stream_video_id` and resource object keys exist.

### Reprocess Webhook Event

- Locate `stripe_event_id` in `stripe_events`.
- Reset processing flag/error if required.
- Run replay command/job for deterministic reprocessing.

### Manual Entitlement Override

- Grant/revoke entitlement via protected internal command.
- Record reason and operator identity in audit trail.

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
