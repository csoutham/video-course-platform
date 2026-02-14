# 17. Milestone 4 Backlog

This file tracks hardening, operational tooling, and auditability improvements.

## Milestone 4 Goal

Harden security and operational controls with auditable events, deterministic replay paths, and runbook-ready commands.

## Task IDs

Local task IDs use `VC-M4-XX`.

## Task Board

| Task ID | Title | Priority | Status | Depends On |
|---|---|---|---|---|
| VC-M4-01 | Add structured audit log storage for security-sensitive actions | Urgent | `done` | Milestone 3 done |
| VC-M4-02 | Instrument checkout/webhook/claim/resource actions with audit events | Urgent | `done` | VC-M4-01 |
| VC-M4-03 | Add operational commands for entitlement grant/revoke and Stripe replay | High | `done` | VC-M4-01 |
| VC-M4-04 | Add hardening tests for operations commands and audit event emission | Urgent | `done` | VC-M4-02, VC-M4-03 |
| VC-M4-05 | Update operational/runbook docs with new command and audit workflows | High | `done` | VC-M4-03 |

## Implementation Progress Notes

- Added `audit_logs` table and `AuditLog` model for structured event auditing.
- Instrumented high-risk flows:
  - checkout initiation
  - webhook processing and replay
  - entitlement grant/revoke
  - resource download request/serve/deny
  - purchase claim completion
- Added operations commands in `routes/console.php`:
  - `videocourses:stripe-reprocess {event_id}`
  - `videocourses:entitlement-grant {user_id} {course_id} {order_id}`
  - `videocourses:entitlement-revoke {user_id} {course_id}`
- Added command coverage in `tests/Feature/OperationsCommandsTest.php`.
- Extended feature tests to validate audit events in checkout, webhook, and resource flows.

## Change Log

- 2026-02-14: Created Milestone 4 backlog and started hardening implementation.
- 2026-02-14: Marked VC-M4-01 through VC-M4-05 done after audit logging, ops commands, and hardening tests were completed.
