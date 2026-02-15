# 20. Milestone 6 Backlog

This file tracks learner progress implementation after the core learning experience launch.

## Milestone 6 Goal

Introduce individual learning progress tracking with a staged rollout:

- Phase 1: lesson-level progress state.
- Phase 2: in-video playback progress.

## Task IDs

Local task IDs use `VC-M6-XX`.

## Task Board

| Task ID | Title | Priority | Status | Depends On |
|---|---|---|---|---|
| VC-M6-01 | Add `lesson_progress` schema and Eloquent model | Urgent | `done` | Milestone 3 done |
| VC-M6-02 | Record lesson progress on learner route access (`in_progress`) | High | `done` | VC-M6-01 |
| VC-M6-03 | Implement explicit lesson completion action in player UI | High | `done` | VC-M6-01 |
| VC-M6-04 | Surface per-lesson completion indicators in module navigation | Medium | `done` | VC-M6-01 |
| VC-M6-05 | Add acceptance tests for progress creation/completion/authorization | Urgent | `done` | VC-M6-02, VC-M6-03 |
| VC-M6-06 | Plan Phase 2 video-progress event ingestion contract | Medium | `todo` | VC-M6-05 |

## Progress State Rules (Phase 1)

- `in_progress`: first entitled access to a lesson.
- `completed`: explicit learner action in the player.
- Completion is per user per lesson.

## Phase 2 Target (Not Yet Implemented)

- Persist playback position and completion percentage per user per lesson.
- Add throttled heartbeat endpoint for player telemetry updates.
- Keep provider integration abstract to support future non-Cloudflare players.

## Change Log

- 2026-02-15: Created Milestone 6 backlog and started VC-M6-01.
- 2026-02-15: Marked VC-M6-01 through VC-M6-05 done after lesson-progress schema, player UX updates, and feature tests were implemented.
