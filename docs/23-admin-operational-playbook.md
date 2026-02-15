# 23. Admin Operational Playbook

## Purpose

Define how operations should be executed once the Filament admin panel is in place.

## Daily Operations

1. Review latest Stripe events for failures.
2. Review paid orders and gifts for anomalies.
3. Review entitlement exceptions.
4. Check audit logs for admin-side mutations.

## Core Incident Procedures

### Payment succeeded, access missing

1. Locate order by `stripe_checkout_session_id`.
2. Confirm webhook record exists and is processed.
3. Trigger Stripe event replay action.
4. Validate entitlement row and user visibility in `My Courses`.

### Gift purchased, recipient cannot claim

1. Locate gift purchase by recipient email/order.
2. Check gift status (`delivered|claimed|revoked`).
3. Verify claim token validity (purpose `gift_claim`, expiry, consumed).
4. If expired, issue new token and resend gift email action.

### Refund processed

1. Confirm order status `refunded`.
2. Confirm entitlements linked to order are `revoked`.
3. If gift order, confirm gift status `revoked`.

## Admin Actions Policy

### Allowed direct actions

- publish/unpublish content
- reorder curriculum
- entitlement grant/revoke
- webhook replay
- gift email resend

### Restricted actions

- hard delete payment records
- manual mutation of Stripe IDs
- manual mutation of audit log history

## Audit Standards

Every mutation action must capture:

- operator user ID
- timestamp
- action type
- target model ID
- reason/context payload

## QA Checklist for Admin Release

1. Non-admin cannot open `/admin`.
2. Admin can CRUD content models.
3. Admin can run entitlement and replay actions.
4. Audit entries created for write actions.
5. Customer journeys still pass existing feature tests.

## Phase 2 Extensions (Not in Phase 1)

- richer analytics dashboards
- bulk operations
- scheduled operational jobs
- advanced role/permission matrix

