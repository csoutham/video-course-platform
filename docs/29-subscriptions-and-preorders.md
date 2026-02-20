# 29. Subscriptions and Preorders (v1 Plan)

## Summary

Add two monetization capabilities on top of the current one-time purchase flow:

1. Stripe-backed subscriptions (monthly/yearly) that unlock all published courses unless explicitly excluded.
2. Stripe-backed preorders for coming-soon courses with a discounted preorder price and release-time charging.

This plan preserves existing flows: one-time checkout, gifts, claim links, receipts, entitlement rules, and learning UI.

## Implementation Status (2026-02-20)

- Completed:
  - Admin UI isolation under dedicated `/admin` shell.
  - Subscription schema/model foundation (`billing_settings`, `subscriptions`, course/order extensions).
  - Subscription checkout endpoint (`/checkout/subscription`) with login requirement and Stripe Checkout `mode=subscription`.
  - Billing pages (`/billing`, `/billing/portal`) and admin billing settings (`/admin/billing`).
  - Stripe webhook subscription sync for `customer.subscription.*` and `invoice.*`.
  - Subscription-aware course access and My Courses inclusion for non-excluded published courses.
- In progress:
  - Preorder reservation/charge lifecycle and release command.

## Product Decisions Locked

- Subscription management: Stripe Customer Portal.
- Subscription cancellation policy: access remains until period end.
- Course detail CTA strategy: encourage one-off purchase first, with subscription as alternate option.
- Preorder visibility: coming-soon pages.
- Preorder payment timing: collect card now, charge at release.
- Preorder discount model: explicit preorder price amount.
- Preorder release behavior: auto-publish at release.
- Preorder gifting: out of scope for v1.

## Data Model Changes

### New table: `billing_settings` (singleton)

- `id`
- `stripe_subscription_monthly_price_id` (nullable)
- `stripe_subscription_yearly_price_id` (nullable)
- `subscription_currency` (`usd|gbp`)
- `stripe_billing_portal_enabled` (bool)
- `stripe_billing_portal_configuration_id` (nullable)
- timestamps

### New table: `subscriptions`

- `id`
- `user_id` (nullable, indexed)
- `email`
- `stripe_customer_id` (indexed)
- `stripe_subscription_id` (unique)
- `stripe_price_id`
- `interval` (`monthly|yearly`)
- `status` (`active|trialing|past_due|canceled|incomplete|incomplete_expired|unpaid`)
- `current_period_start` (nullable)
- `current_period_end` (nullable, indexed)
- `cancel_at_period_end` (bool)
- `canceled_at` (nullable)
- `ended_at` (nullable)
- timestamps

### Extend `courses`

- `is_subscription_excluded` (bool default false)
- `is_preorder_enabled` (bool default false)
- `preorder_starts_at` (nullable datetime)
- `preorder_ends_at` (nullable datetime)
- `release_at` (nullable datetime)
- `preorder_price_amount` (nullable unsigned integer)
- `stripe_preorder_price_id` (nullable)

### New table: `preorder_reservations`

- `id`
- `course_id` (indexed)
- `user_id` (nullable, indexed)
- `email` (indexed)
- `stripe_customer_id` (indexed)
- `stripe_setup_intent_id` (unique)
- `stripe_payment_method_id`
- `reserved_price_amount`
- `currency`
- `status` (`reserved|charge_pending|charged|action_required|failed|canceled`)
- `release_at` (indexed)
- `charged_order_id` (nullable FK `orders.id`)
- `stripe_payment_intent_id` (nullable)
- `charged_at` (nullable)
- `failure_code` (nullable)
- `failure_message` (nullable)
- timestamps

### Extend `orders`

- `order_type` (`one_time|subscription|preorder`) default `one_time`
- `subscription_id` nullable FK `subscriptions.id`

### Extend claim tokens

- Extend `purchase_claim_tokens.purpose` to include `preorder_claim`.

## Public Interfaces

### New customer routes

- `POST /checkout/subscription`
  - input: `interval` (`monthly|yearly`), guest `email`, optional `promotion_code`
  - output: redirect to Stripe Checkout (`mode=subscription`)

- `GET /billing`
  - auth page showing current subscription state and management actions

- `POST /billing/portal`
  - auth redirect to Stripe Customer Portal

- `POST /preorder/{course}`
  - input: guest `email` if not authenticated
  - output: redirect to Stripe Checkout (`mode=setup`)
  - guards: preorder enabled + preorder window active

### Existing route changes

- `POST /checkout/{course}` keeps one-time behavior.
- Block regular checkout when course is preorder-only and not released.

### Admin routes

- `GET /admin/billing`, `PUT /admin/billing` for subscription plan configuration.
- Extend course create/edit payloads with subscription exclusion and preorder fields.

## Stripe Contract

### Checkout session metadata

- Subscription checkout:
  - `flow=subscription`
  - `interval=monthly|yearly`
  - `source=videocourses-web`

- Preorder setup checkout:
  - `flow=preorder_setup`
  - `course_id`
  - `release_at`
  - `source=videocourses-web`

### Webhook events consumed

- Existing:
  - `checkout.session.completed`
  - `checkout.session.async_payment_succeeded`
  - `checkout.session.async_payment_failed`
  - `charge.refunded`

- New:
  - `customer.subscription.created`
  - `customer.subscription.updated`
  - `customer.subscription.deleted`
  - `invoice.paid`
  - `invoice.payment_failed`

## Access Rules

User can access a course when either:

1. Active entitlement exists for that course.
2. Active subscription exists, course is published, and `is_subscription_excluded=false`.

## Preorder Lifecycle

1. Admin configures preorder window, release date, and preorder price.
2. Customer reserves during window via Setup mode checkout.
3. Reservation stores customer + payment method + locked price.
4. At release, scheduled command:
   - auto-publishes course if needed
   - attempts off-session PaymentIntent charge
5. On success:
   - creates preorder `order` + `order_items`
   - grants entitlement for linked user
   - creates claim token for guest reservation
   - sends receipt/claim email
6. On failure:
   - marks reservation failed/action-required
   - sends recovery email with fallback checkout link.

## Services and Components

- Add `SubscriptionCheckoutService`
- Add `SubscriptionSyncService` (webhook sync)
- Add `SubscriptionAccessService` (or fold into `CourseAccessService`)
- Add `PreorderCheckoutService`
- Add `PreorderReleaseService`
- Add `BillingPortalService`
- Add `videocourses:preorders-release` command and scheduler entry

## UI/UX Changes

- Admin course form:
  - checkbox: `Exclude from subscription`
  - preorder section: enable flag, start/end/release timestamps, preorder price, refresh Stripe preorder price

- Admin billing page:
  - monthly/yearly Stripe price IDs
  - portal config fields

- Course detail page:
  - one-time purchase remains primary CTA
  - subscription shown as alternate CTA when eligible
  - preorder mode shows release date, preorder price, reserve CTA

- My Courses:
  - include subscription-covered courses.

## Testing Plan

### Feature tests

- subscription checkout start (guest/auth)
- billing portal redirect
- subscription webhook create/update/delete with idempotency
- course access via subscription
- exclusion flag blocks subscription access for excluded course
- preorder reservation creation (setup checkout)
- preorder release command success path
- preorder release command failure path
- preorder guest claim flow
- existing one-time/gift/claim/receipt regressions

### Unit tests

- access-resolution logic (entitlement vs subscription precedence)
- preorder release charge transition rules
- subscription status mapping from Stripe to local model

## Rollout

1. Deploy schema and code with feature flags off:
   - `SUBSCRIPTIONS_ENABLED=false`
   - `PREORDERS_ENABLED=false`
2. Configure admin billing settings.
3. Enable subscriptions in staging; run end-to-end webhook validation.
4. Enable preorders in staging; run release command dry run and live test.
5. Enable scheduler for release command in production.
6. Gradually enable flags in production.

## Assumptions

- Subscription plans are global to the platform, not course-specific.
- Subscription does not replace one-time purchase in v1.
- Preorders are single-course reservations.
- Existing Stripe receipt flow continues for charged orders.
