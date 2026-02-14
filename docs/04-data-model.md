# 04. Data Model

## Entity Overview

- `users`: customer identity and authentication.
- `courses`: purchasable course product.
- `course_modules`: grouped sections inside a course.
- `course_lessons`: ordered lesson items within modules.
- `lesson_resources`: downloadable files attached to lessons.
- `orders`: checkout and payment outcomes.
- `order_items`: line items, one course per item for v1.
- `entitlements`: access grants from paid orders.
- `stripe_events`: idempotent webhook processing ledger.

## Suggested Fields

### users

- `id`
- `name`
- `email` (unique)
- `password`
- `email_verified_at`
- `created_at`, `updated_at`

### courses

- `id`
- `slug` (unique)
- `title`
- `description`
- `thumbnail_url`
- `price_amount`
- `price_currency`
- `stripe_price_id`
- `is_published`
- `created_at`, `updated_at`

### course_modules

- `id`
- `course_id` (index)
- `title`
- `sort_order`
- `created_at`, `updated_at`

### course_lessons

- `id`
- `course_id` (index)
- `module_id` (index)
- `title`
- `slug`
- `summary`
- `stream_video_id`
- `sort_order`
- `is_published`
- `created_at`, `updated_at`

### lesson_resources

- `id`
- `lesson_id` (index)
- `name`
- `storage_key`
- `mime_type`
- `size_bytes`
- `sort_order`
- `created_at`, `updated_at`

### orders

- `id`
- `user_id` (nullable for initial guest purchase)
- `email`
- `stripe_checkout_session_id` (unique)
- `stripe_customer_id` (nullable)
- `status` (`pending|paid|failed|refunded`)
- `subtotal_amount`
- `discount_amount`
- `total_amount`
- `currency`
- `paid_at` (nullable)
- `refunded_at` (nullable)
- `created_at`, `updated_at`

### order_items

- `id`
- `order_id` (index)
- `course_id` (index)
- `unit_amount`
- `quantity` (default 1)
- `created_at`, `updated_at`

### entitlements

- `id`
- `user_id` (index)
- `course_id` (index)
- `order_id` (index)
- `status` (`active|revoked`)
- `granted_at`
- `revoked_at` (nullable)
- `created_at`, `updated_at`

Constraints:

- Unique active entitlement per `user_id + course_id`.

### stripe_events

- `id`
- `stripe_event_id` (unique)
- `event_type`
- `payload_json`
- `processed_at` (nullable)
- `processing_error` (nullable)
- `created_at`, `updated_at`

## Relationship Notes

- One course has many modules and lessons.
- One module has many lessons.
- One lesson has many resources.
- One order has many order items.
- One paid order creates entitlements for related items.

## Soft Delete / Audit Policy

- Prefer immutable payment records.
- Avoid deleting orders/events.
- Revoke entitlements instead of deleting entitlement history.
