# 14. Milestone 1 Backlog

This backlog replaces the previously created external issues and is now the canonical Milestone 1 task list.

## Milestone 1 Goal

Bootstrap the foundation: Laravel app, auth, content schema, public catalog/detail pages, and test-first CI baseline.

## Task IDs

Local task IDs use `VC-M1-XX`.

## Task Board

| Task ID | Title | Priority | Status | Depends On |
|---|---|---|---|---|
| VC-M1-01 | Scaffold Laravel app with Livewire and Tailwind baseline | High | `done` | None |
| VC-M1-02 | Implement authentication and password reset flow | High | `done` | VC-M1-01 |
| VC-M1-03 | Create core content schema for courses/modules/lessons/resources | High | `todo` | VC-M1-01 |
| VC-M1-04 | Build public course catalog page with curated listing | High | `todo` | VC-M1-03 |
| VC-M1-05 | Build public course detail page with curriculum preview and purchase CTA placeholder | High | `todo` | VC-M1-03 |
| VC-M1-06 | Establish test harness and CI quality gates | Urgent | `done` | VC-M1-01 |
| VC-M1-07 | Write Milestone 1 acceptance tests for public catalog and auth gates | Urgent | `todo` | VC-M1-02, VC-M1-04, VC-M1-05, VC-M1-06 |
| VC-M1-08 | Add deterministic factories and seeders for catalog test data | High | `todo` | VC-M1-03 |

## Task Details

### VC-M1-01: Scaffold Laravel app with Livewire and Tailwind baseline

Status: `done`

Scope:

- Create new Laravel installation.
- Keep `/docs` in place.
- Verify framework boots.
- Run baseline tests.

Acceptance:

- `php artisan --version` succeeds.
- Default tests pass.
- Repository initialized with initial commit.

Completion notes:

- Laravel `12.51.0` scaffolded.
- Baseline tests passing.
- Initial commit created.

### VC-M1-02: Implement authentication and password reset flow

Status: `done`

Scope:

- Registration/login/logout.
- Password reset request and completion.
- Route protection for auth-only areas.

Acceptance:

- Feature tests for successful and invalid auth/reset flows.
- Guest and auth middleware behavior verified.

Completion notes:

- Installed Laravel Breeze with Livewire stack.
- Added auth routes, controllers, views, and profile management pages.
- Added password reset and authentication feature tests.
- Verified via `composer test`.

### VC-M1-03: Create core content schema for courses/modules/lessons/resources

Status: `todo`

Scope:

- Migrations for core content hierarchy.
- Eloquent models and relations.
- Ordering and publish flags.

Acceptance:

- Migrations run cleanly.
- Relationship tests verify hierarchy and ordering.
- Unpublished content excluded from public queries.

### VC-M1-04: Build public course catalog page with curated listing

Status: `todo`

Scope:

- `/courses` route and rendering.
- Published course listing.
- Empty state.

Acceptance:

- Anonymous access works.
- Unpublished courses hidden.
- Feature tests cover populated/empty states.

### VC-M1-05: Build public course detail page with curriculum preview and purchase CTA placeholder

Status: `todo`

Scope:

- `/courses/{slug}` page.
- Curriculum preview with modules/lessons.
- Buy CTA placeholder for Milestone 2.

Acceptance:

- Anonymous access works.
- 404 for missing or unpublished course.
- Feature tests for valid/invalid slug and publish state.

### VC-M1-06: Establish test harness and CI quality gates

Status: `done`

Scope:

- Set up unit + feature structure.
- Add CI workflow for tests on PRs.
- Define pass/fail merge gates.

Acceptance:

- CI runs tests on each PR.
- Failing tests block merges.
- Commands and CI behavior documented.

Completion notes:

- Added GitHub Actions workflow at `.github/workflows/ci.yml`.
- CI now runs:
  - `composer test`
  - `./vendor/bin/pint --test`
  - `npm run build`
- Local verification passed for all quality-gate commands.

### VC-M1-07: Write Milestone 1 acceptance tests for public catalog and auth gates

Status: `todo`

Scope:

- Acceptance scenarios for Milestone 1 paths.
- Stable, deterministic tests.

Acceptance:

- Scenario coverage mapped to `09-testing-acceptance.md`.
- Tests pass locally and in CI.

### VC-M1-08: Add deterministic factories and seeders for catalog test data

Status: `todo`

Scope:

- Factories for course hierarchy.
- Seeders for local demo/test data.
- Published and unpublished variants.

Acceptance:

- Tests can build hierarchy quickly with factories.
- Local seed command yields usable catalog.
- Data shape documented.

## Execution Order

1. VC-M1-06 (quality gates early)
2. VC-M1-02 and VC-M1-03 (parallel)
3. VC-M1-08
4. VC-M1-04 and VC-M1-05
5. VC-M1-07

## Risks and Mitigations

- Risk: Feature implementation outpaces testing.
  - Mitigation: Do not mark tasks done without test evidence.
- Risk: Schema churn causes test brittleness.
  - Mitigation: Keep factories centralized and deterministic.
- Risk: Scope creep toward marketplace behavior.
  - Mitigation: Enforce curated per-business model from `01-product-scope.md`.

## Change Log

- 2026-02-14: Migrated backlog from deleted external tracker into `/docs`.
- 2026-02-14: Marked VC-M1-06 done after adding CI workflow and validating quality gates.
- 2026-02-14: Marked VC-M1-02 done after Breeze Livewire auth scaffolding and passing auth tests.
