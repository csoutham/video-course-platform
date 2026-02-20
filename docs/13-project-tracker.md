# 13. Project Tracker

This file is the source of truth for project execution tracking in-repo.

## Tracking Policy

- Do not use external trackers for this project.
- Track scope, status, blockers, and next actions in `/docs`.
- Keep milestone backlog docs updated as work changes.
- Update this file first when priorities shift.

## Status Legend

- `todo`: Not started.
- `in_progress`: Active work.
- `blocked`: Waiting on external dependency/decision.
- `done`: Completed and verified.

## Milestone Status

| Milestone | Scope | Status | Owner | Notes |
|---|---|---|---|---|
| Milestone 1 | Foundation (Laravel, auth, schema, public catalog/detail, test baseline) | `done` | Chris/Codex | Backlog in `14-milestone-1-backlog.md` |
| Milestone 2 | Checkout + webhooks + entitlements | `done` | Chris/Codex | Backlog in `15-milestone-2-backlog.md` |
| Milestone 3 | Learning experience and secure resource delivery | `done` | Chris/Codex | Backlog in `16-milestone-3-backlog.md` |
| Milestone 4 | Hardening, operations, and expanded tests | `done` | Chris/Codex | Backlog in `17-milestone-4-backlog.md` |
| Milestone 5 | Open-source preparation | `done` | Chris/Codex | Backlog in `18-milestone-5-backlog.md` |
| Milestone 6 | Learner progress tracking (Phase 1 lesson progress, Phase 2 video progress) | `in_progress` | Chris/Codex | Backlog in `20-milestone-6-backlog.md` |
| Milestone 7 | Frontend redesign + admin panel | `in_progress` | Chris/Codex | Specs in `21-frontend-redesign-spec.md`, `22-admin-panel-architecture.md`, `23-admin-operational-playbook.md` |
| Milestone 8 | Mobile API + Expo playback app | `in_progress` | Chris/Codex | Specs in `27-mobile-playback-strategy.md`, `mobile-architecture.md`, `api/mobile-v1.md` |

## Active Sprint Focus

- Current focus: Milestone 7 frontend redesign Phase A/B implementation.
- Exit criteria reference: `11-implementation-roadmap.md`.
- Execution task list: `20-milestone-6-backlog.md` then Milestone 7 specs (`21-23`).

## Decision Log

| Date | Decision | Rationale |
|---|---|---|
| 2026-02-14 | Keep project management in `/docs` instead of Linear | Single source of truth inside repository |
| 2026-02-14 | Treat testing as release gate from Milestone 1 onward | Future open-source readiness and regression control |

## Change Log

- 2026-02-14: Created in-repo tracking baseline and replaced external tracker dependency.
- 2026-02-14: Marked Milestone 1 as `in_progress` after CI/test quality gates implementation started.
- 2026-02-14: Marked Milestone 1 as `done` after all backlog tasks reached completion criteria.
- 2026-02-14: Completed Milestone 1 authentication/password-reset baseline with Livewire Breeze.
- 2026-02-14: Completed Milestone 1 core content schema and relationship tests.
- 2026-02-14: Added deterministic course factories and seed data for repeatable local/dev testing.
- 2026-02-14: Completed public catalog/detail routes and corresponding acceptance tests for Milestone 1.
- 2026-02-14: Started Milestone 2 with dedicated in-repo backlog and implementation sequencing.
- 2026-02-14: Completed Milestone 2 payment schema, checkout endpoint, webhook idempotency flow, and acceptance tests.
- 2026-02-14: Completed guest purchase claim-linking flow and marked Milestone 2 complete.
- 2026-02-14: Started Milestone 3 implementation with learner routes and access-control focus.
- 2026-02-14: Completed Milestone 3 learner library, gated playback, secure resource download flow, and acceptance tests.
- 2026-02-14: Started Milestone 4 hardening and operational tooling implementation.
- 2026-02-14: Completed Milestone 4 with structured audit logs, replay/manual operations commands, and hardening test coverage.
- 2026-02-14: Completed Milestone 5 open-source readiness artifacts and CI compatibility matrix.
- 2026-02-14: Added Cloudflare Stream iframe playback URL integration in learner player flow.
- 2026-02-15: Added Milestone 6 plan and started Phase 1 per-lesson learner progress implementation.
- 2026-02-15: Completed Milestone 6 Phase 1 (lesson-level progress tracking and completion indicators); Phase 2 video-progress remains planned.
- 2026-02-15: Completed Milestone 6 Phase 2 with video heartbeat persistence, auto-completion thresholds, and player telemetry wiring.
- 2026-02-16: Added Milestone 7 planning docs for frontend redesign and Filament admin panel architecture.
- 2026-02-16: Started Milestone 7 frontend redesign implementation with shared visual system and core customer-surface restyling.
- 2026-02-16: Started Milestone 7 admin implementation with custom `auth + is_admin` dashboard foundation (`/admin`, `/admin/courses`, `/admin/orders`) and access-control tests.
- 2026-02-16: Added admin course CRUD with automatic Stripe price provisioning and manual refresh flow.
- 2026-02-16: Added nested module/lesson CRUD in admin course editor with Cloudflare Stream video lookup and duration sync support.
- 2026-02-17: Added free lead-magnet distribution path (self-enroll, claim-link mode, and free gifting) with admin controls and coverage tests.
- 2026-02-17: Added explicit `stream_video_filter_term` on course forms to filter Cloudflare Stream options per course in admin edit.
- 2026-02-17: Added lesson module reassignment in admin editor and Markdown rendering for lesson summaries on learner playback.
- 2026-02-17: Updated lesson editor field layout so `Slug`, `Sort`, and `Duration` align in one row for faster editing.
- 2026-02-17: Improved collapsed module summaries in admin curriculum with clearer total/live lesson badges.
- 2026-02-17: Switched course thumbnails to local uploads and added PDF uploads for course/module/lesson resources.
- 2026-02-17: Added Kit purchaser sync (Stripe + free checkout), course-level Kit tag support, and tagging documentation.
- 2026-02-17: Restricted receipts to Stripe-backed non-zero orders; free/non-Stripe orders no longer send or show receipts.
- 2026-02-17: Improved responsive UX with right-drawer mobile nav and playback layout changes (video-first on small screens, sticky scrollable curriculum on large screens).
- 2026-02-17: Moved mobile enrollment panel on course detail to appear after Requirements and before curriculum for stronger pre-sale conversion flow.
- 2026-02-17: Removed inline Blade PHP from core customer pages (catalog/detail/player/my-courses) and standardized primary CTA buttons across checkout/course/learning views.
- 2026-02-17: Added shared form/alert utility classes (`vc-label`, `vc-help`, `vc-error`, `vc-alert*`, `vc-input-sm`) and started applying them across customer claim/enrollment forms and admin filters.
- 2026-02-18: Unified auth UI primitives (labels, inputs, validation, status alerts, primary/secondary/danger buttons) through shared Blade components.
- 2026-02-18: Applied shared admin form primitives across course create/edit and Udemy import screens, including checkbox consistency and help/error messaging.
- 2026-02-18: Added Sanctum-based mobile API surface (`/api/v1/mobile`) with token auth, library/course/playback/progress/resource endpoints, and mobile API feature tests.
- 2026-02-18: Added Expo mobile app workspace (`/mobile`) with auth, library, course outline, lesson playback, and resource handoff flows.
- 2026-02-19: Extracted mobile app to standalone repository `git@github.com:csoutham/video-course-platform-mobile.git` and removed `/mobile` from this monorepo.
- 2026-02-19: Added runtime white-label branding system (admin-managed platform name, logo, and core color token overrides) without rebuilding compiled Tailwind assets.
- 2026-02-19: Extended branding with runtime font selection (system/Bunny/Google) and dynamic font stylesheet injection without rebuilding assets.
- 2026-02-19: Extended branding with runtime logo sizing, publisher/footer copy controls, and CMS-style editable `/courses` homepage hero text.
- 2026-02-19: Refactored admin Branding edit screen to tabbed sections with a sticky top toolbar and persisted active-tab state.
- 2026-02-19: Switched image upload paths (course thumbnails, branding logos) to configurable R2-backed disk (`IMAGE_UPLOAD_DISK`) for zero-downtime-safe asset delivery.
- 2026-02-20: Added explicit Flysystem S3 adapter dependency (`league/flysystem-aws-s3-v3`) to fix R2 upload runtime class resolution.
- 2026-02-20: Added `MEDIA_PUBLIC_URL` runtime media base URL support for thumbnails/logos so deployed public domains can be changed without data rewrites.
- 2026-02-20: Replaced inline admin status banners with fixed top-right toast notifications to prevent content layout shifts.
- 2026-02-20: Stabilized `LearningAccessTest` by forcing local resource disk in suite setup after R2 default-disk changes.
