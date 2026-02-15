# 11. Implementation Roadmap

## Milestone 1: Foundation

- Bootstrap Laravel app with Livewire and Tailwind.
- Implement auth and password reset.
- Create core schema for courses/modules/lessons/resources.
- Build public catalog and course detail pages.

Definition of done:

- Public browsing works from seeded/internal data.

## Milestone 2: Checkout and Entitlements

- Implement Stripe Checkout session creation.
- Implement webhook ingestion with signature verification.
- Add orders, items, and idempotent event ledger.
- Build entitlement grant pipeline.
- Add guest purchase claim flow.

Definition of done:

- Successful payment reliably results in course access.

## Milestone 3: Learning Experience

- Build `/my-courses` learner library.
- Build `/learn/{course}/{lesson?}` player flow.
- Integrate Cloudflare Stream playback context.
- Implement secure resource download endpoint with R2 signed URLs.

Definition of done:

- Entitled users can watch lessons and download resources.

## Milestone 4: Hardening and Operations

- Complete security controls and audit logs.
- Implement complete test coverage for critical acceptance scenarios and CI quality gates.
- Finalize runbook and operational commands.

Definition of done:

- Platform is production-ready for internal operation and passes open-source quality baseline.

## Milestone 5: Open-Source Preparation

- Add contributor documentation for setup, architecture, and testing.
- Define semantic versioning and changelog process.
- Add CI matrix for supported PHP/Laravel versions.
- Add issue and pull request templates.
- Validate installability from clean environment using only public docs.

Definition of done:

- Repository can be evaluated and contributed to externally with reproducible results.

## Milestone 6: Learner Progress Tracking

- Phase 1: track per-lesson progress (`in_progress`, `completed`) per user.
- Surface completed-state indicators in learner navigation.
- Add explicit complete action in learner player.
- Add acceptance tests for progress creation and completion authorization.
- Phase 2 (next): add in-video progress persistence (playback position and percent complete).

Definition of done:

- Entitled users have deterministic per-lesson progress state and completion markers.

## Release Checklist

1. All acceptance scenarios passing.
2. Webhook retries and replay verified.
3. Entitlement revocation on refund validated.
4. Documentation in `/docs` reviewed and approved.
5. CI quality gates and coverage thresholds are enforced.
