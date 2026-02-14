# Contributing to VideoCourses

## Development Workflow

1. Create a branch from `main`.
2. Implement changes with tests.
3. Run local quality gates:
   - `composer test`
   - `./vendor/bin/pint --test`
   - `npm run build`
4. Update `/docs` milestone trackers when scope or status changes.
5. Open a pull request using the template.

## Coding Standards

- Follow Laravel conventions for naming and structure.
- Prefer small, focused commits using conventional commit messages.
- Keep customer-facing behavior covered by feature tests.
- Keep infrastructure/security-sensitive logic auditable.

## Testing Requirements

- New features require feature tests.
- Business logic requires unit or feature-level regression coverage.
- Bug fixes require a failing test first when practical.
- Pull requests should not reduce meaningful test coverage.

## Documentation Requirements

- Update relevant docs in `/docs` when behavior or workflows change.
- Keep runbook and milestone backlog status aligned with implementation.

## Pull Request Expectations

- Clear summary of what changed and why.
- Linked docs updates.
- Test evidence from local runs.
- Notes on backward compatibility or migration impact.
