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
- `subscriptions`: Stripe subscription state per user/customer.
- `billing_settings`: singleton Stripe subscription + portal configuration.
- `preorder_reservations`: setup-intent reservations and release-charge outcomes.
- `course_reviews`: learner and manually imported rating/review records.
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
- `description` (subtitle / short marketing summary)
- `long_description` (nullable markdown source)
- `requirements` (nullable markdown source)
- `thumbnail_url`
- `intro_video_id` (nullable; Cloudflare Stream UID for public course intro video)
- `stream_video_filter_term` (nullable; admin-defined filter for course edit Stream picker)
- `source_payload_json` (nullable)
- `source_last_imported_at` (nullable timestamp)
- `price_amount`
- `price_currency`
- `stripe_price_id`
- `is_free` (boolean; when true, Stripe checkout is bypassed)
- `free_access_mode` (`claim_link|direct`)
- `is_published`
- `is_subscription_excluded` (boolean; excludes course from platform subscription access)
- `is_preorder_enabled` (boolean)
- `preorder_starts_at` (nullable timestamp)
- `preorder_ends_at` (nullable timestamp)
- `release_at` (nullable timestamp)
- `preorder_price_amount` (nullable integer)
- `stripe_preorder_price_id` (nullable Stripe price id for preorder pricing)
- `reviews_approved_count` (cached approved review count)
- `rating_average` (cached average from approved reviews)
- `rating_distribution_json` (cached 1-5 rating distribution for approved reviews)
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
- `course_id` (index)
- `module_id` (nullable index)
- `lesson_id` (nullable index)
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
- Uses Stripe session IDs for paid checkouts and `free_*` synthetic IDs for free enrollments.
- `stripe_customer_id` (nullable)
- `status` (`pending|paid|failed|partially_refunded|refunded`)
- `order_type` (`one_time|subscription|preorder`)
- `subscription_id` (nullable FK for recurring subscription invoice orders)
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
- `purpose` (`order_claim|gift_claim|preorder_claim`)
- `gift_purchase_id` (nullable unique for gift claim tokens)

### billing_settings

- `id` (singleton row)
- `stripe_subscription_monthly_price_id` (nullable)
- `stripe_subscription_yearly_price_id` (nullable)
- `subscription_currency` (`usd|gbp`)
- `stripe_billing_portal_enabled` (boolean)
- `stripe_billing_portal_configuration_id` (nullable)
- `created_at`, `updated_at`

### subscriptions

- `id`
- `user_id` (nullable index)
- `email`
- `stripe_customer_id` (index)
- `stripe_subscription_id` (unique)
- `stripe_price_id`
- `interval` (`monthly|yearly`)
- `status` (`active|trialing|past_due|canceled|incomplete|incomplete_expired|unpaid`)
- `current_period_start` (nullable)
- `current_period_end` (nullable index)
- `cancel_at_period_end` (boolean)
- `canceled_at` (nullable)
- `ended_at` (nullable)
- `created_at`, `updated_at`

### preorder_reservations

- `id`
- `course_id` (index)
- `user_id` (nullable index)
- `email` (index)
- `stripe_customer_id` (index)
- `stripe_setup_intent_id` (unique)
- `stripe_payment_method_id`
- `reserved_price_amount`
- `currency`
- `status` (`reserved|charge_pending|charged|action_required|failed|canceled`)
- `release_at` (index)
- `charged_order_id` (nullable FK `orders.id`)
- `stripe_payment_intent_id` (nullable)
- `charged_at` (nullable)
- `failure_code` (nullable)
- `failure_message` (nullable)
- `created_at`, `updated_at`

### course_reviews

- `id`
- `course_id` (index)
- `user_id` (nullable index)
- `source` (`native|udemy_manual`)
- `reviewer_name` (nullable; used for manual imports)
- `rating` (`1..5`)
- `title` (nullable)
- `body` (nullable)
- `status` (`pending|approved|rejected|hidden`)
- `original_reviewed_at` (nullable timestamp, used for imported historical dates)
- `last_submitted_at` (nullable)
- `approved_at` (nullable)
- `approved_by_user_id` (nullable)
- `rejected_at` (nullable)
- `rejected_by_user_id` (nullable)
- `hidden_at` (nullable)
- `hidden_by_user_id` (nullable)
- `moderation_note` (nullable)
- `created_at`, `updated_at`

Constraints:

- Unique native review per learner per course: (`course_id`, `user_id`)
- Multiple imported rows allowed via nullable `user_id`

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
- Course, module, and lesson-level resources share a single table; each row belongs to one scope.
- One lesson has many user progress rows.
- One order has many order items.
- One paid order creates entitlements for related items.
- Gift purchases map one-to-one to orders and may later create entitlement for recipient user.

## Soft Delete / Audit Policy

- Prefer immutable payment records.
- Avoid deleting orders/events.
- Revoke entitlements instead of deleting entitlement history.
