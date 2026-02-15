# 09. Testing and Acceptance

## Test Strategy

- Feature tests for end-to-end business flows (required).
- Unit tests for entitlement, order transition, and policy services (required).
- Browser/e2e tests for high-value journeys (required before public/open-source release).
- Contract tests for Stripe webhook payload handling and idempotency (required).

## Quality Gates

- No release when any test suite fails.
- CI pipeline must run unit + feature + contract tests on every pull request.
- Minimum coverage target: 90% for domain services and policy classes, 80% project-wide baseline.
- Mutation testing recommended for entitlement and payment state transition logic.
- Static analysis and coding standards checks must pass in CI.

Current Milestone 1 CI command gates:

- `composer test`
- `./vendor/bin/pint --test`
- `npm run build`

## Critical Acceptance Scenarios

1. Guest can purchase published course and later claim account with access preserved.
2. Existing authenticated user can purchase additional course and see it in library.
3. User without entitlement is blocked from `/learn/{course}`.
4. User without entitlement cannot download lesson resource.
5. Stripe webhook replay with same event ID is idempotent.
6. Checkout cancel path creates no entitlement.
7. Refund webhook transitions order to refunded and revokes entitlement.
8. Signed resource link expires and cannot be reused after expiry.
9. Guest purchase linked to existing email account does not create duplicate users.
10. Course with unpublished lessons never exposes unpublished lesson playback URLs.
11. Concurrent webhook deliveries for same session remain idempotent under race conditions.
12. Entitled learner opening a lesson creates `in_progress` lesson progress.
13. Entitled learner can mark a lesson complete and see completion state.
14. Unentitled learner cannot write lesson progress for protected lessons.
12. Policy denial paths return safe responses without leaking resource metadata.

## Suggested Test Matrix

- Auth state: guest, authenticated entitled, authenticated unentitled.
- Payment result: success, cancel, async fail, refunded.
- Content state: published/unpublished course and lessons.
- Resource state: valid object key, missing object key, expired signed URL.

## Regression Focus

- Entitlement grants only on verified successful payment.
- No duplicate entitlements from repeated webhooks.
- Course player navigation respects module/lesson ordering.
- Changes to checkout metadata mapping do not break webhook reconciliation.
- Access revocation always invalidates lesson and resource access immediately.

## Open-Source Readiness Test Requirements

- Include deterministic fixture data for local and CI runs.
- Ensure tests can run with only documented environment variables.
- Maintain contributor-facing test commands in repository docs.
- Add smoke-test workflow for supported PHP and Laravel versions before public release.
