# VideoCourses Documentation

This folder contains the implementation-ready documentation for VideoCourses v1, a web-based platform to browse, buy, and watch purchasable courses.

## Scope Summary

- Curated course catalog for a single creator, business, or project deployment.
- One-time course purchases via Stripe Checkout with coupon support.
- Guest checkout with post-purchase account claim flow.
- Authenticated learner area for course playback.
- Course structure with modules, lessons, and downloadable resources.
- Cloudflare Stream for video delivery.
- Cloudflare R2 signed URLs for downloadable resources.
- Internal-only content management via database and CLI (no admin CMS UI in v1).
- Testing-first delivery with CI quality gates suitable for future open-source release.

## Chosen Product Defaults

- Access model: permanent entitlement after purchase unless manually revoked.
- Refunds: manual operations in Stripe Dashboard.
- Tax: no automated in-app tax handling in v1.
- Auth: Laravel email/password and password reset.

## Reading Order

1. [01-product-scope](./01-product-scope.md)
2. [02-user-journeys](./02-user-journeys.md)
3. [03-system-architecture](./03-system-architecture.md)
4. [04-data-model](./04-data-model.md)
5. [05-customer-facing-routes-and-livewire](./05-customer-facing-routes-and-livewire.md)
6. [06-stripe-checkout-and-webhooks](./06-stripe-checkout-and-webhooks.md)
7. [07-video-and-resource-delivery](./07-video-and-resource-delivery.md)
8. [08-security-risk-controls](./08-security-risk-controls.md)
9. [09-testing-acceptance](./09-testing-acceptance.md)
10. [10-operations-runbook](./10-operations-runbook.md)
11. [11-implementation-roadmap](./11-implementation-roadmap.md)
12. [12-open-source-readiness](./12-open-source-readiness.md)
