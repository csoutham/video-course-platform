# 15. Milestone 2 Backlog

This file tracks implementation of checkout, webhooks, and entitlement grants.

## Milestone 2 Goal

Implement Stripe-powered one-time checkout and idempotent webhook processing so successful payments reliably grant course access.

## Task IDs

Local task IDs use `VC-M2-XX`.

## Task Board

| Task ID | Title | Priority | Status | Depends On |
|---|---|---|---|---|
| VC-M2-01 | Add payment domain schema (orders, order_items, entitlements, stripe_events) | Urgent | `done` | Milestone 1 done |
| VC-M2-02 | Implement checkout session creation endpoint for courses | Urgent | `done` | VC-M2-01 |
| VC-M2-03 | Implement Stripe webhook endpoint with signature verification and idempotency | Urgent | `done` | VC-M2-01 |
| VC-M2-04 | Implement entitlement grant/revoke service based on order transitions | High | `done` | VC-M2-01, VC-M2-03 |
| VC-M2-05 | Implement checkout success/cancel routes and customer-facing status messaging | High | `done` | VC-M2-02 |
| VC-M2-06 | Implement guest purchase linking strategy and account-claim preparation hooks | High | `in_progress` | VC-M2-02, VC-M2-03 |
| VC-M2-07 | Add Milestone 2 acceptance tests (checkout initiation, webhook idempotency, entitlement grant/revoke) | Urgent | `done` | VC-M2-02, VC-M2-03, VC-M2-04 |

## Notes

- Refunds remain manual in Stripe Dashboard, but webhook sync must reflect `refunded` state and entitlement revocation.
- Coupon support uses Stripe Checkout native coupon/promotion handling.
- Guest checkout is allowed; purchase must be recoverable to user account via email/claim flow.

## Implementation Progress Notes

- VC-M2-01 complete:
  - Added migrations/models for `orders`, `order_items`, `entitlements`, and `stripe_events`.
  - Added relationship and casting coverage in `tests/Feature/PaymentDomainSchemaTest.php`.
- VC-M2-02 complete:
  - Added `POST /checkout/{course}` endpoint.
  - Added `StripeCheckoutService` for hosted checkout session URL generation.
  - Added guest email and optional promotion code handling from course detail page.
- VC-M2-03 complete:
  - Added `POST /webhooks/stripe` endpoint with signature verification.
  - Added idempotent event ledger persistence in `stripe_events`.
- VC-M2-04 complete:
  - Added order state transitions (`paid`, `failed`, `refunded`) and entitlement grant/revoke handling.
- VC-M2-05 complete:
  - Added `/checkout/success` and `/checkout/cancel` pages.
- VC-M2-06 in progress:
  - Checkout metadata now stores `course_id`, `customer_email`, and optional `user_id` for future claim-linking.
  - Remaining: explicit account-claim flow implementation and reconciliation command.
- VC-M2-07 complete:
  - Added acceptance tests for checkout initiation, webhook idempotency, and entitlement grant/revoke.

## Change Log

- 2026-02-14: Created Milestone 2 backlog.
- 2026-02-14: Marked VC-M2-01 through VC-M2-05 and VC-M2-07 complete after checkout/webhook implementation and tests.
- 2026-02-14: Marked VC-M2-06 as `in_progress` pending explicit account-claim flow implementation.
