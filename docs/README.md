# VideoCourses Documentation

This folder contains the implementation-ready documentation for VideoCourses v1, a web-based platform to browse, buy, and watch purchasable courses.
Docs version: `0.9.6`.

## Scope Summary

- Curated course catalog for a single creator, business, or project deployment.
- One-time course purchases via Stripe Checkout with coupon support.
- Optional gift purchases with recipient claim links and email delivery.
- Optional free lead-magnet distribution (self-enroll and free gifting).
- Guest checkout with post-purchase account claim flow.
- Authenticated learner area for course playback.
- Authenticated receipt downloads for paid orders.
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
13. [13-project-tracker](./13-project-tracker.md)
14. [14-milestone-1-backlog](./14-milestone-1-backlog.md)
15. [15-milestone-2-backlog](./15-milestone-2-backlog.md)
16. [16-milestone-3-backlog](./16-milestone-3-backlog.md)
17. [17-milestone-4-backlog](./17-milestone-4-backlog.md)
18. [18-milestone-5-backlog](./18-milestone-5-backlog.md)
19. [19-stripe-cloudflare-setup](./19-stripe-cloudflare-setup.md)
20. [20-milestone-6-backlog](./20-milestone-6-backlog.md)
21. [21-frontend-redesign-spec](./21-frontend-redesign-spec.md)
22. [22-admin-panel-architecture](./22-admin-panel-architecture.md)
23. [23-admin-operational-playbook](./23-admin-operational-playbook.md)
24. [24-udemy-importer-spec](./24-udemy-importer-spec.md)
25. [25-free-lead-magnet-flow](./25-free-lead-magnet-flow.md)
26. [26-kit-integration](./26-kit-integration.md)
