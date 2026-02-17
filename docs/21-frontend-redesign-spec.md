# 21. Frontend Redesign Spec

## Objective

Upgrade the customer-facing UI from functional/basic to a modern premium editorial style while preserving existing
behavior and routes.

## Design Direction

- Visual style: modern premium editorial.
- Keep interaction calm and focused (no heavy motion).
- Prioritize readability, hierarchy, and trust in purchase/claim flows.
- Sales orientation on public pages:
    - catalog cards must highlight visual identity (thumbnail/hero treatment)
    - detail hero should support an intro video (when configured) with thumbnail fallback
    - pricing must be immediately visible in both list and detail surfaces
    - course detail should use a two-column sales layout (content left, purchase panel right)
- SEO-first implementation for public sales pages:
    - unique title + description per page
    - Open Graph/Twitter tags for social sharing previews
    - canonical URL on catalog and detail pages

## Scope

### In scope

- Shared visual system (tokens + reusable UI primitives).
- Navigation and page-header consistency.
- Restyle high-impact customer pages:
    - catalog
    - course detail + checkout form
    - checkout success + claim pages
    - my courses
    - my gifts
    - receipts
    - player visual polish

### Out of scope

- Re-platforming frontend stack.
- Functional changes to Stripe/Cloudflare integrations.
- Rewriting learning-player architecture.

## Implementation Plan

### Phase A: UI Foundation

1. Create design tokens in `resources/app.css`:
    - color roles
    - spacing scale
    - border radius and shadows
    - typography scale
2. Create/standardize reusable Blade components:
    - section header
    - card variants
    - button variants
    - badge/status styles
    - form field wrappers (label/help/error)
3. Unify container width and spacing rules across app/public layouts.

### Phase B: Core Commerce Pages

1. Restyle `courses/catalog`.
2. Restyle `courses/detail` (including gift form state clarity and right-side purchase rail).
3. Restyle checkout success + claim + gift-claim pages.
4. Ensure empty/loading/error states are consistent.
5. Add/verify SEO metadata coverage for catalog + detail.

### Phase C: Library and Learning Surfaces

1. Restyle `my-courses`, `my-gifts`, and `receipts`.
2. Improve player page readability:
    - left curriculum panel scannability
    - lesson metadata clarity
    - footer action hierarchy (prev / completion / next)

### Phase D: QA + Accessibility

1. Run responsive pass (mobile/tablet/desktop).
2. Validate color contrast and focus rings.
3. Validate form error/label accessibility.
4. Remove visual inconsistencies and orphan utility patterns.
5. Ensure mobile nav uses an off-canvas right drawer and playback page prioritizes video before curriculum.
6. Minimize Blade template logic by moving formatting/derived values to PHP classes and controllers.

## Technical Constraints

- Keep Laravel + Livewire + Tailwind as-is.
- Preserve route names and existing business logic.
- Avoid JS-heavy animation dependencies.

## Test and Acceptance

### Acceptance criteria

1. UI quality is visibly upgraded across all customer journeys.
2. Existing checkout, claim, gift, and learning behaviors remain unchanged.
3. Mobile and desktop layouts are stable.
4. Accessibility baseline is met for key forms and navigation.

### Validation

- Existing feature suite remains green.
- Add focused UI assertions where practical (critical labels/CTAs/feedback states).
