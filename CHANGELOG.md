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
