# 28. Branding and White-Labeling (Runtime)

## Goal

Enable deployment owners to configure platform branding without rebuilding Tailwind assets or manually uploading files.

## What Is Included

- Runtime platform name override.
- Runtime logo upload and replacement.
- Runtime logo presentation size override (`--vc-logo-height`).
- Runtime typography selection:
    - provider: `system`, `bunny`, or `google`
    - font family
    - font weights
- Runtime core palette overrides via CSS variables:
    - `--vc-bg`
    - `--vc-panel`
    - `--vc-panel-soft`
    - `--vc-border`
    - `--vc-text`
    - `--vc-muted`
    - `--vc-brand`
    - `--vc-brand-strong`
    - `--vc-accent`
    - `--vc-warning`
- Cached branding payload for low-overhead request rendering.
- Admin UI under `/admin/branding`.
- Publisher/footer content controls:
    - publisher name
    - publisher website
    - footer tagline
- CMS-lite homepage hero copy controls for `/courses`:
    - eyebrow
    - title
    - subtitle

## Technical Design

### Data Model

- `branding_settings` singleton table:
    - `platform_name`
    - `logo_url`
    - `logo_height_px`
    - `font_provider`
    - `font_family`
    - `font_weights`
    - `publisher_name`
    - `publisher_website`
    - `footer_tagline`
    - `homepage_eyebrow`
    - `homepage_title`
    - `homepage_subtitle`
    - token-specific color columns
    - timestamps

### Runtime Injection

- Tailwind build output remains static.
- Layouts inject runtime token overrides via inline `<style>` in `<head>`.
- Layouts conditionally inject provider-specific font stylesheet links at runtime.
- Header logos use runtime `--vc-logo-height` and do not require recompilation.
- If settings are missing/disabled/invalid, defaults from `config/branding.php` are used.

### Service Layer

- `App\Services\Branding\BrandingService` handles:
    - resolving current branding
    - validation-safe normalization of colors
    - logo storage and old-logo cleanup
    - cache reads/writes/flush
- `App\Data\BrandingData` is the runtime DTO shared with views.

### View Sharing

- `AppServiceProvider` registers a global view composer to provide `$branding`.
- Navigation, footer, guest/public layouts, and customer-facing SEO titles consume `$branding`.
- Catalog hero copy consumes `$branding` homepage fields.

## Admin UX

Page: `GET /admin/branding`

Actions:

- `PUT /admin/branding`:
    - save platform name
    - save runtime font provider/family/weights
    - save logo display size
    - save publisher/footer text and URL
    - save homepage hero copy
    - save color tokens
    - optionally upload logo
- `POST /admin/branding/reset`:
    - reset name and colors to defaults
    - remove stored logo reference

Validation:

- `platform_name`: required, max 120.
- `logo`: image, max 5MB, `jpg|jpeg|png|webp`.
- `logo_height_px`: required integer, `16-120`.
- `font_provider`: `system|bunny|google`.
- `font_family`: alphanumeric + space + dash.
- `font_weights`: comma-separated hundreds (`400,500,700`).
- `publisher_name`: required, max 120.
- `publisher_website`: nullable valid URL, max 255.
- `footer_tagline`: nullable, max 255.
- `homepage_eyebrow`: nullable, max 80.
- `homepage_title`: nullable, max 160.
- `homepage_subtitle`: nullable, max 500.
- color fields: strict `#RRGGBB`.

## Configuration

Additions in `.env`:

```dotenv
BRANDING_ENABLED=true
BRANDING_CACHE_KEY=branding:current
BRANDING_CACHE_TTL_SECONDS=3600
BRANDING_DISK=public
BRANDING_DEFAULT_PLATFORM_NAME="${APP_NAME}"
BRANDING_DEFAULT_FONT_PROVIDER=bunny
BRANDING_DEFAULT_FONT_FAMILY=Figtree
BRANDING_DEFAULT_FONT_WEIGHTS=400,500,600,700
BRANDING_DEFAULT_LOGO_HEIGHT_PX=32
BRANDING_DEFAULT_PUBLISHER_NAME="${APP_NAME}"
BRANDING_DEFAULT_PUBLISHER_WEBSITE=
BRANDING_DEFAULT_FOOTER_TAGLINE="Practical video training for real-world results."
BRANDING_DEFAULT_HOMEPAGE_EYEBROW="Professional Training"
BRANDING_DEFAULT_HOMEPAGE_TITLE="Learn faster with curated, results-focused courses."
BRANDING_DEFAULT_HOMEPAGE_SUBTITLE="Each course is designed for implementation. Buy once, get immediate access, and follow clear module-based lessons with downloadable resources."
```

`config/branding.php` holds defaults and token fallback values.

## Operational Notes

- Ensure `public` disk is writable.
- Ensure `php artisan storage:link` is present in deploy flow.
- Cache clears automatically on branding save/reset.
- No asset rebuild is required for branding changes.

## Testing Coverage

Feature tests cover:

- admin authorization for branding routes
- branding page rendering
- update and runtime style token injection
- runtime font provider/family link injection
- logo upload persistence
- invalid color rejection
- reset to defaults behavior
