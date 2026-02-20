# 06. Stripe Checkout and Webhooks

## Checkout Strategy

- Use Stripe Checkout hosted page.
- Map each course to a Stripe Price ID.
- Map subscription plans (monthly/yearly) from admin-configured Stripe price IDs.
- Use Stripe Billing Portal for self-service subscription management.
- Use Stripe Checkout `mode=setup` for preorder reservations.
- Allow course-level free mode (`is_free`) that bypasses Stripe and creates local zero-value orders.
- Support optional promotion/coupon code entry.
- Support optional gift checkout mode with recipient details.
- Allow guest purchase; bind entitlement after claim/account association.
- Send purchase receipt email after successful paid webhook processing for Stripe-backed, non-zero orders.
- Include claim link in receipt email for eligible Stripe guest purchases.

## Session Metadata Requirements

Include metadata sufficient to reconcile internal records:

- `course_id` (or order draft key)
- `customer_email`
- `promotion_code` (if provided)
- `is_gift` (`0|1`)
- `recipient_email` (if gift)
- `recipient_name` (if gift and provided)
- `gift_message_present` (`0|1`)
- `source` (optional analytics)
- `flow` (`subscription|preorder_setup|...`) for non-standard checkout paths

## Webhook Events Consumed

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`
- `checkout.session.async_payment_failed`
- `charge.refunded`
- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`
- `invoice.paid`
- `invoice.payment_failed`

## Webhook Controller Rules

1. Verify Stripe signature header.
2. Persist `stripe_event_id` uniquely before processing.
3. If event already processed, return success (idempotent).
4. Resolve associated session/order.
5. Apply deterministic state transition.
6. Grant or revoke entitlement accordingly.
7. Send receipt email after commit for first paid transition.
8. For gift orders, create gift record and send recipient + buyer gift emails.
9. Mark event processed timestamp.
10. For preorder setup sessions, create/update preorder reservation and skip order/entitlement creation.
11. For subscription events, sync local subscription state and create subscription invoice orders on `invoice.paid`.

## Order State Transitions

- `pending -> paid` on successful checkout/payment event.
- `pending -> failed` on async payment failure.
- `paid -> refunded` on refund event.
- Subscription invoice orders are recorded as `order_type=subscription` with `stripe_checkout_session_id=subinv_<invoice_id>`.

## Entitlement Transitions

- Grant `active` entitlement on first successful paid state.
- Ensure duplicate events do not create duplicate entitlements.
- Revoke entitlement on refunded order according to policy.
- Gift order: skip buyer entitlement grant; grant entitlement only when recipient claims.
- Subscription access: no per-course entitlement row required when active subscription is valid and course is not excluded.

## Error Handling

- Store processing exceptions in `stripe_events.processing_error`.
- Retry failed processing via queue/command.
- Keep endpoint returning 2xx only after durable persistence of event record.

## Local and Test Guidance

- Use Stripe test keys and webhook secret.
- Use Stripe CLI forwarding for local webhook testing.
- Keep separate test products/prices from production.

## Free Lead-Magnet Path (Non-Stripe)

- Trigger: course has `is_free=true`.
- App creates local paid-equivalent order (`total_amount=0`, synthetic `free_*` session id).
- Self enroll:
  - `free_access_mode=direct` + authenticated user grants entitlement immediately.
  - otherwise app issues normal purchase claim token and uses claim-link flow.
- Free gift enroll:
  - app creates gift purchase + gift claim token.
  - app sends recipient and buyer gift emails.
- No Stripe receipt email is sent for free or non-Stripe orders.
- Webhook processing is not involved for this path.
