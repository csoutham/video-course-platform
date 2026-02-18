# 12. Open-Source Readiness

## Objective

Prepare Video Courses for eventual public release without re-architecting the core product.

## Repository Readiness Standards

- Clear setup documentation for local development.
- Deterministic test suite that runs in CI and local environments.
- Explicit architectural boundaries (payments, entitlement, media delivery).
- Stable public interfaces and semver-based release discipline.

## Contribution Baseline

- Add `CONTRIBUTING.md` with coding, test, and PR expectations.
- Add `CODE_OF_CONDUCT.md`.
- Add issue templates for bug report and feature request.
- Add pull request template requiring:
    - Linked issue/context.
    - Test evidence.
    - Backward compatibility notes.

## CI/CD Requirements

- Run static analysis and coding standards checks.
- Run unit, feature, and contract tests on every pull request.
- Add CI matrix for supported PHP and Laravel versions.
- Block merge when quality gates fail.

## Versioning and Release Policy

- Use semantic versioning.
- Maintain `CHANGELOG.md` with categorized entries.
- Tag releases and publish upgrade notes when breaking changes occur.

## Security and Disclosure

- Add `SECURITY.md` with vulnerability reporting path.
- Keep dependency updates and CVE checks in routine maintenance.
- Document secret-handling expectations for local and production usage.

## Documentation Quality Bar

- Keep `/docs` implementation docs synchronized with behavior changes.
- Include examples for common extension points (payment provider abstraction, media provider abstraction).
- Keep operational runbook current with deployment and incident procedures.

## Exit Criteria for Public Release

1. All quality gates consistently green for defined period.
2. Installation and quickstart validated by a fresh external-like environment.
3. Core journeys validated against acceptance scenarios.
4. Security posture and disclosure policy documented and published.
