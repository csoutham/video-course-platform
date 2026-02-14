# 18. Milestone 5 Backlog

This file tracks open-source preparation and contributor-readiness tasks.

## Milestone 5 Goal

Prepare repository governance, contribution flow, release discipline, and CI compatibility checks for public collaboration.

## Task IDs

Local task IDs use `VC-M5-XX`.

## Task Board

| Task ID | Title | Priority | Status | Depends On |
|---|---|---|---|---|
| VC-M5-01 | Add contributor governance docs (`CONTRIBUTING`, `CODE_OF_CONDUCT`, `SECURITY`) | Urgent | `done` | Milestone 4 done |
| VC-M5-02 | Add release process docs (`CHANGELOG` + semver policy notes) | High | `done` | VC-M5-01 |
| VC-M5-03 | Add issue and pull request templates | High | `done` | VC-M5-01 |
| VC-M5-04 | Expand CI compatibility checks (PHP version matrix) | Urgent | `done` | VC-M5-03 |
| VC-M5-05 | Validate installability/docs flow from clean setup path | High | `done` | VC-M5-01, VC-M5-04 |

## Implementation Progress Notes

- Added governance docs:
  - `CONTRIBUTING.md`
  - `CODE_OF_CONDUCT.md`
  - `SECURITY.md`
- Added release log baseline:
  - `CHANGELOG.md`
- Added collaboration templates:
  - `.github/ISSUE_TEMPLATE/bug_report.yml`
  - `.github/ISSUE_TEMPLATE/feature_request.yml`
  - `.github/pull_request_template.md`
- Expanded CI to PHP matrix (`8.3`, `8.4`) in `.github/workflows/ci.yml`.
- Verified installability and setup flow with:
  - `composer run setup`
  - `composer test`
  - `./vendor/bin/pint --test`
  - `npm run build`

## Change Log

- 2026-02-14: Created Milestone 5 backlog and started implementation.
- 2026-02-14: Marked VC-M5-01 through VC-M5-05 done after governance docs, templates, CI matrix, and setup validation were completed.
