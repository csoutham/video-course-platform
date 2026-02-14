# 16. Milestone 3 Backlog

This file tracks learner experience and protected media/resource access.

## Milestone 3 Goal

Deliver learner library, entitlement-gated course player, and secure downloadable resources.

## Task IDs

Local task IDs use `VC-M3-XX`.

## Task Board

| Task ID | Title | Priority | Status | Depends On |
|---|---|---|---|---|
| VC-M3-01 | Implement learner library route and page (`/my-courses`) | Urgent | `done` | Milestone 2 done |
| VC-M3-02 | Implement entitlement-gated course player route (`/learn/{course}/{lesson?}`) | Urgent | `done` | VC-M3-01 |
| VC-M3-03 | Implement lesson/module navigation and lesson selection behavior | High | `done` | VC-M3-02 |
| VC-M3-04 | Implement secure resource download flow with signed URLs | Urgent | `done` | VC-M3-02 |
| VC-M3-05 | Add Milestone 3 acceptance tests for entitled/unentitled access paths | Urgent | `done` | VC-M3-01, VC-M3-02, VC-M3-04 |

## Implementation Progress Notes

- Implemented learner library route and page at `/my-courses`.
- Implemented course player route at `/learn/{course}/{lesson?}` with entitlement checks.
- Implemented module/lesson navigation and default lesson selection behavior.
- Implemented signed resource download flow:
  - `/resources/{resource}/download`
  - `/resources/{resource}/stream` with signed URL + user binding validation.
- Added acceptance coverage in `tests/Feature/LearningAccessTest.php`.

## Change Log

- 2026-02-14: Created Milestone 3 backlog and started implementation.
- 2026-02-14: Marked VC-M3-01 through VC-M3-05 done after learner routes, secure resource downloads, and acceptance tests were implemented.
