# 01. Product Scope

## Goal

Deliver a customer-facing web application where users can browse a curated course catalog, purchase courses, and view entitled lessons and downloadable resources.

## Target Users

- External customers purchasing course content.
- Internal operators maintaining content and handling support operations.
- Primary deployment model: one person, one business, or one project per installation.

## In Scope (v1)

- Public course listing and detail pages.
- Checkout via Stripe with one-time payments and coupons.
- Guest checkout with purchase-to-account claim flow.
- Login, registration, and password reset.
- Learner area listing purchased courses.
- Course player with module/lesson navigation.
- Downloadable lesson resources with entitlement checks.
- Curated catalog operations (small/medium catalog size, not marketplace-scale).

## Out of Scope (v1)

- Admin CMS for course authoring.
- Marketplace features (multi-vendor listings, instructor payouts, discovery ranking).
- Subscription billing models.
- In-app refund request flow.
- Automated tax calculation.
- Multi-currency and regional pricing matrix.

## Non-Functional Requirements

- Secure entitlement enforcement for all protected content.
- Reliable payment-to-access handoff via webhooks.
- Operational simplicity for internal team.
- Auditable purchase and entitlement changes.
- High test coverage and repeatable CI checks as release gate.
- Open-source readiness: clear extension points, docs coverage, and deterministic behavior.

## Operational Content Management

Content data is managed via:

- Database seeding and direct table operations.
- Artisan commands for import/update flows.

No customer-visible dependency on internal admin tools is required for v1 launch.
