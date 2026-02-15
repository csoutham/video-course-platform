# 22. Admin Panel Architecture

## Objective

Replace DB/manual operational workflows with a secure, maintainable admin interface.

## Selected Approach

- Framework: **Filament**.
- Admin URL: `/admin`.
- Access control: authenticated users with `users.is_admin = true`.

## Why Filament

- Best fit for current Laravel + Livewire stack.
- Fast CRUD/resource delivery.
- Lower build and maintenance overhead than custom admin.
- No Nova license dependency.

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

1. Install Filament and set up admin auth.
2. Add core resources and relation managers.
3. Add operational actions and audit integration.
4. Add smoke and policy tests.
5. Enable in production after QA pass.

## Acceptance Criteria

1. Core workflows no longer require direct DB edits.
2. Admin-only access is enforced.
3. Operational actions are available for common incidents.
4. Existing customer-facing flows are unaffected.

