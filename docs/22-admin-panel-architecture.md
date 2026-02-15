# 22. Admin Panel Architecture

## Objective

Replace DB/manual operational workflows with a secure, maintainable admin interface.

## Selected Approach

- Framework: **Custom Laravel/Blade admin (phase 1)** with optional Filament migration later.
- Admin URL: `/admin`.
- Access control: authenticated users with `users.is_admin = true`.

## Why This Approach

- Delivers immediate operational value with minimal dependency risk.
- Keeps admin UX aligned with existing public layout and Tailwind system.
- Enables incremental shipping in small commits with focused feature tests.
- Preserves future migration path to Filament if CRUD complexity grows.

## Current Implementation Status (Live)

- `users.is_admin` is implemented and enforced through `auth + admin` middleware.
- `/admin` dashboard is implemented with operational metrics and recent orders.
- `/admin/courses` read-only listing is implemented.
- `/admin/orders` read-only listing with status filter is implemented.
- Feature tests cover guest/non-admin/admin access control and page visibility.

## Admin Scope (Phase 1)

### Content

- Courses
- Modules
- Lessons
- Lesson resources

### Commerce

- Orders
- Order items (relation manager)
- Gift purchases

### Access

- Entitlements
- Purchase claim tokens

### Operations

- Stripe events (read + replay action)
- Audit logs (read/search/filter)

## Data and Authorization

### Required data change

- Add `users.is_admin` boolean (default false).

### Policy model

- Enforce per-resource policies.
- Separate read-only resources (ledger/events/logs) from editable resources.
- Restrict destructive actions for payment history models.

## Admin Actions (Phase 1)

1. Publish/unpublish courses and lessons.
2. Reorder modules/lessons.
3. Manual entitlement grant/revoke (with reason logging).
4. Stripe event replay.
5. Gift support actions:
   - resend gift claim email
   - revoke gift status where needed

## Dashboard (Phase 1)

Lightweight operational widgets only:

- published course count
- paid orders (recent period)
- active entitlement count
- delivered vs claimed gifts

No advanced analytics in phase 1.

## Security Requirements

1. Admin routes protected by auth + admin gate.
2. All mutations audited with operator context.
3. Sensitive models protected from unsafe edits.
4. Non-admin users receive deny response for `/admin/*`.

## Rollout Plan

1. Ship custom read-only operational screens (`dashboard`, `courses`, `orders`) with tests.
2. Add editable course/module/lesson/resource forms with audit logging.
3. Add commerce operations (gift resend, entitlement adjustments, webhook replay).
4. Add policy tests and admin mutation regression tests.
5. Re-evaluate Filament migration after phase 1 once operational workload is known.

## Acceptance Criteria

1. Core workflows no longer require direct DB edits.
2. Admin-only access is enforced.
3. Operational actions are available for common incidents.
4. Existing customer-facing flows are unaffected.
