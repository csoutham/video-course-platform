# 23. Admin Operational Playbook

## Purpose

Define how operations are executed in the custom `/admin` panel and expanded over time.

## Current Admin Surface (Implemented)

- `/admin` dashboard with key operational metrics.
- `/admin/courses` course CRUD with Stripe price provisioning options.
- `/admin/courses/{course}/edit` module + lesson CRUD with Cloudflare Stream video picker.
- `/admin/orders` read-only order ledger with status filter.
- All admin routes require `auth` and `is_admin`.

## Daily Operations

1. Review latest Stripe events for failures.
2. Review paid orders and gifts for anomalies.
3. Review entitlement exceptions.
4. Check audit logs for admin-side mutations.
5. Validate new lesson uploads appear in the Cloudflare Stream selector in course edit.

## Course Content Operations

### Create a new paid course

1. Open `/admin/courses/create`.
2. Enter title, slug/description, and pricing.
3. Keep `Auto-create Stripe price` enabled for first publish.
4. Save and confirm `stripe_price_id` is populated on the edit page.

### Add modules and lessons

1. Open `/admin/courses/{course}/edit`.
2. Add module rows and set sort order.
3. Add lessons under each module.
4. Select `Cloudflare Stream video` where available.
5. Use `Sync` when editing a lesson to pull duration from Stream metadata.

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
2. Admin can open dashboard, courses, and orders admin pages.
3. Feature tests exist for admin access control and route visibility.
4. Customer journeys still pass existing feature tests.
5. Any new write actions are audited before production release.

## Phase 2 Extensions (Not in Phase 1)

- richer analytics dashboards
- bulk operations
- scheduled operational jobs
- advanced role/permission matrix
