# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and this project follows Semantic Versioning.

## [Unreleased]

### Added

- Foundation architecture documentation under `/docs`.
- Milestone 1 implementation: auth, course schema, public catalog/detail, CI quality gates.
- Milestone 2 implementation: Stripe checkout, webhook idempotency, orders/entitlements, guest claim flow.
- Milestone 3 implementation: learner library, gated course player, signed resource download flow.
- Milestone 4 implementation: structured audit logs and operational commands.
- Checkout success page now resolves Stripe `session_id` and renders guest claim CTA when available.
- Feature test coverage for checkout success guest-claim and linked-account states.
- Added explicit Stripe + Cloudflare environment and operations setup guide in `docs/19-stripe-cloudflare-setup.md`.
- Added authenticated navigation links to `My Courses` in app and public layouts.
- Catalog now routes logged-in users with active entitlements directly to the learner course player.
- Added Phase 1 learner progress tracking with per-lesson `in_progress` and `completed` states.
- Added lesson completion action and completion indicators in learner player navigation.
- Added feature test coverage for lesson progress creation, completion, and access control.
- Course player now defaults `/learn/{course}` to the next incomplete lesson for returning learners.
- Added previous/next lesson navigation controls in the learner player.
- Added optional Cloudflare Stream signed-token playback mode with configuration and test coverage.
- Added Phase 2 video progress tracking with heartbeat endpoint and per-lesson playback metrics.
- Added automatic lesson completion on configurable video progress threshold.
- Unified logged-in navigation/layout source via shared layout partial across app/public layouts.
- Guest homepage now routes to the course catalog grid.
- Added Cloudflare Stream duration sync command and lesson length display in curriculum/player views.
- Replaced dashboard-first authenticated flow with `My Courses` defaults and navigation links.
- Added authenticated receipts index and per-order Stripe-hosted receipt links.
- Added post-purchase receipt email delivery on successful Stripe paid webhooks.
- Guest purchase receipt emails now include the claim-purchase link.
- Added logout action to shared logged-in navigation.
- Added gift purchase flow with recipient checkout fields and gift claim links.
- Added webhook-backed gift purchase records, gift claim token lifecycle, and gift redemption flow.
- Added gift delivery and buyer confirmation emails plus authenticated `My Gifts` tracking page.
- Added planning docs for frontend redesign and Filament admin-panel architecture under `/docs/21-23`.
- Added frontend redesign Phase A/B foundation: shared visual tokens/components and upgraded catalog, course detail, checkout, claim, and library surfaces.
- Added Sanctum-based mobile API v1 under `/api/v1/mobile` for token auth, library access, course detail, playback context, progress heartbeats, and signed resource links.
- Added Expo React Native mobile workspace under `/mobile` with login, course library, course lessons, playback screen, and account/logout flow.

### Fixed

- Stripe checkout no longer sends mutually exclusive `allow_promotion_codes` and `discounts` parameters together when a promotion code is provided.
- Promotion code checkout now resolves human-entered code strings to active Stripe `promo_...` identifiers and returns a validation error for invalid/inactive codes.
