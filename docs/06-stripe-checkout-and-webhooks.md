# 06. Stripe Checkout and Webhooks

## Checkout Strategy

- Use Stripe Checkout hosted page.
- Map each course to a Stripe Price ID.
- Support optional promotion/coupon code entry.
- Allow guest purchase; bind entitlement after claim/account association.

## Session Metadata Requirements

Include metadata sufficient to reconcile internal records:

- `course_id` (or order draft key)
- `customer_email`
- `promotion_code` (if provided)
- `source` (optional analytics)

## Webhook Events Consumed

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`
- `checkout.session.async_payment_failed`
- `charge.refunded`

## Webhook Controller Rules

1. Verify Stripe signature header.
2. Persist `stripe_event_id` uniquely before processing.
3. If event already processed, return success (idempotent).
4. Resolve associated session/order.
5. Apply deterministic state transition.
6. Grant or revoke entitlement accordingly.
7. Mark event processed timestamp.

## Order State Transitions

- `pending -> paid` on successful checkout/payment event.
- `pending -> failed` on async payment failure.
- `paid -> refunded` on refund event.

## Entitlement Transitions

- Grant `active` entitlement on first successful paid state.
- Ensure duplicate events do not create duplicate entitlements.
- Revoke entitlement on refunded order according to policy.

## Error Handling

- Store processing exceptions in `stripe_events.processing_error`.
- Retry failed processing via queue/command.
- Keep endpoint returning 2xx only after durable persistence of event record.

## Local and Test Guidance

- Use Stripe test keys and webhook secret.
- Use Stripe CLI forwarding for local webhook testing.
- Keep separate test products/prices from production.
