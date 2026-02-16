# 04. Data Model

## Entity Overview

- `users`: customer identity and authentication.
- `courses`: purchasable course product.
- `course_modules`: grouped sections inside a course.
- `course_lessons`: ordered lesson items within modules.
- `lesson_resources`: downloadable files attached to lessons.
- `lesson_progress`: per-user lesson completion state.
- `orders`: checkout and payment outcomes.
- `order_items`: line items, one course per item for v1.
- `gift_purchases`: gift order metadata and recipient claim status.
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
- `source_platform` (nullable, indexed; e.g. `udemy`)
- `source_url` (nullable, unique)
- `source_external_id` (nullable, indexed)
- `slug` (unique)
- `title`
- `description`
- `thumbnail_url`
- `intro_video_id` (nullable; Cloudflare Stream UID for public course intro video)
- `source_payload_json` (nullable)
- `source_last_imported_at` (nullable timestamp)
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
- `is_imported_shell` (default false)
- `source_external_key` (nullable; deterministic import key)
- `created_at`, `updated_at`

### course_lessons

- `id`
- `course_id` (index)
- `module_id` (index)
- `title`
- `slug`
- `summary`
- `stream_video_id`
- `duration_seconds` (nullable; synced from Stream metadata)
- `sort_order`
- `is_published`
- `is_imported_shell` (default false)
- `source_external_key` (nullable; deterministic import key)
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

### lesson_progress

- `id`
- `user_id` (index)
- `lesson_id` (index)
- `status` (`in_progress|completed`)
- `playback_position_seconds` (default 0)
- `video_duration_seconds` (nullable)
- `percent_complete` (0-100)
- `started_at` (nullable)
- `last_viewed_at` (nullable)
- `completed_at` (nullable)
- `created_at`, `updated_at`

### orders

- `id`
- `public_id` (unique non-sequential reference, example `ord_01kh...`, used in customer-facing URLs/receipts)
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

### gift_purchases

- `id`
- `order_id` (unique)
- `course_id` (index)
- `buyer_user_id` (nullable index)
- `buyer_email`
- `recipient_email` (index)
- `recipient_name` (nullable)
- `gift_message` (nullable text)
- `status` (`pending|delivered|claimed|revoked`)
- `delivered_at` (nullable)
- `claimed_by_user_id` (nullable index)
- `claimed_at` (nullable)
- `created_at`, `updated_at`

### purchase_claim_tokens

- Existing table supports both order and gift claims.
- `purpose` (`order_claim|gift_claim`)
- `gift_purchase_id` (nullable unique for gift claim tokens)

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
- One lesson has many user progress rows.
- One order has many order items.
- One paid order creates entitlements for related items.
- Gift purchases map one-to-one to orders and may later create entitlement for recipient user.

## Soft Delete / Audit Policy

- Prefer immutable payment records.
- Avoid deleting orders/events.
- Revoke entitlements instead of deleting entitlement history.
