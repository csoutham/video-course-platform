# 28. Branding and White-Labeling (Runtime)

## Goal

Enable deployment owners to configure platform branding without rebuilding Tailwind assets or manually uploading files.

## What Is Included

- Runtime platform name override.
- Runtime logo upload and replacement.
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

## Technical Design

### Data Model

- `branding_settings` singleton table:
    - `platform_name`
    - `logo_url`
    - `font_provider`
    - `font_family`
    - `font_weights`
    - token-specific color columns
    - timestamps

### Runtime Injection

- Tailwind build output remains static.
- Layouts inject runtime token overrides via inline `<style>` in `<head>`.
- Layouts conditionally inject provider-specific font stylesheet links at runtime.
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

## Admin UX

Page: `GET /admin/branding`

Actions:

- `PUT /admin/branding`:
    - save platform name
    - save runtime font provider/family/weights
    - save color tokens
    - optionally upload logo
- `POST /admin/branding/reset`:
    - reset name and colors to defaults
    - remove stored logo reference

Validation:

- `platform_name`: required, max 120.
- `logo`: image, max 5MB, `jpg|jpeg|png|webp`.
- `font_provider`: `system|bunny|google`.
- `font_family`: alphanumeric + space + dash.
- `font_weights`: comma-separated hundreds (`400,500,700`).
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
