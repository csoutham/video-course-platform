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
| Milestone 3 | Learning experience and secure resource delivery | `todo` | Chris/Codex | Depends on Milestone 2 entitlements |
| Milestone 4 | Hardening, operations, and expanded tests | `todo` | Chris/Codex | Security and runbook completion |
| Milestone 5 | Open-source preparation | `todo` | Chris/Codex | CI matrix, contributor docs, release process |

## Active Sprint Focus

- Current focus: Milestone 3 preparation.
- Exit criteria reference: `11-implementation-roadmap.md`.
- Execution task list: `15-milestone-2-backlog.md`.

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
