# 32. SEO Foundations

## Summary

This document tracks the SEO baseline work for customer-facing pages.

Initial delivery includes:

1. Canonical URL support and robots directives in shared public layout metadata.
2. Route-aware `noindex,nofollow` controls for non-public customer flows.
3. Public `robots.txt` endpoint.
4. Dynamic `sitemap.xml` endpoint for catalog and published course pages.
5. Feature tests for sitemap, robots, canonical tags, and index/noindex behavior.
6. Course-level SEO metadata overrides in admin (`seo_title`, `seo_description`, `seo_image_url`).

## Implemented in Current Slice

### Metadata controls

- Shared SEO helper: `App\Support\Seo\SeoMeta`
  - canonical URL normalization
  - route-aware robots policy defaults
- Public layout outputs:
  - `meta name="robots"`
  - `meta name="googlebot"`
  - canonical URL
  - OpenGraph `og:site_name`
- Guest/Admin layouts now emit `noindex,nofollow`.

### Crawl endpoints

- `GET /robots.txt` (route name: `robots`)
- `GET /sitemap.xml` (route name: `sitemap`)

## Policy

### Indexable pages

- `/courses`
- `/courses/{slug}` for published courses

### Non-indexable pages

- checkout success/cancel and claim flows
- authenticated learner surfaces
- billing/profile pages
- admin pages
- certificate verification pages

## Test Coverage

- `tests/Feature/SeoInfrastructureTest.php`
  - sitemap URL inclusion/exclusion
  - robots directives and sitemap declaration
  - canonical + robots meta on public course page
  - noindex meta on checkout success page

## Next Steps

1. Add structured data in shared layout for `Organization` and `WebSite`.
2. Extend schema assertions and Search Console runbook checks.
3. Add per-course social image upload workflow to replace URL-only entry for SEO image.
