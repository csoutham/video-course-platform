# 03. System Architecture

## Stack

- Backend: Laravel
- UI: Livewire + Tailwind CSS
- Payments: Stripe Checkout + webhooks
- Video delivery: Cloudflare Stream
- Resource storage: Cloudflare R2 (private objects, signed URLs)
- Data store: relational DB (MySQL or PostgreSQL)
- Async: Laravel queues for webhook and email side effects

## Deployment Model

- Intended for a single organization/creator per deployment.
- Catalog is curated and intentionally limited in size compared to marketplace platforms.
- Architecture favors maintainability and testability over hyperscale discovery/ranking features.

## Core Components

1. Laravel App
   - Routing, authorization, domain services, persistence.
   - Web + API surfaces, including `/api/v1/mobile` endpoints for mobile clients.
2. Livewire Components
   - Catalog, detail, learner library, player interactions.
3. Stripe Integration
   - Checkout session creation and webhook ingestion.
4. Entitlement Engine
   - Grants/revokes access tied to order lifecycle.
5. Media Delivery
   - Cloudflare Stream lesson playback.
6. Resource Delivery
   - Signed R2 download URLs generated on demand.
7. Mobile Client (Expo)
   - iOS/Android playback app that consumes installation-bound mobile API.
   - Maintained in separate repo: `git@github.com:csoutham/video-course-platform-mobile.git`.

## High-Level Flow: Purchase

1. User clicks Buy.
2. App creates Stripe Checkout Session for course price (coupon optional).
3. User pays on Stripe-hosted checkout page.
4. Stripe webhook event arrives and is signature-verified.
5. App writes idempotent event record.
6. App finalizes order and grants entitlement.
7. Confirmation and claim/account email is sent.

## High-Level Flow: Learning Access

1. Authenticated user requests learner route.
2. Policy checks active entitlement for course.
3. On pass, lesson metadata is returned.
4. Player loads Stream context.
5. Resource download endpoint issues short-lived signed URL after authorization.

## Idempotency and Reliability

- All Stripe events are stored by unique Stripe event ID.
- Duplicate event deliveries are no-op.
- Order finalization logic is deterministic and re-runnable.
- Queue retries handle transient downstream errors.

## Internal Data Operations

- Course/module/lesson/resource records are managed through DB scripts/CLI.
- Public customer functionality remains independent from admin UIs.
