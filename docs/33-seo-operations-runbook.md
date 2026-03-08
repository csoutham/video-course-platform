# 33. SEO Operations Runbook

## Purpose

Operational guide for maintaining SEO health after rollout.

## Ownership and Cadence

- Owner: Platform operator (or delegated growth/marketing owner).
- Weekly:
  - Review Search Console errors and indexing coverage.
  - Review top landing pages and CTR.
- Monthly:
  - Compare baseline metrics to the previous month.
  - Review course metadata quality and internal-link opportunities.
- Release-time:
  - Run SEO smoke checklist before and after deployment.

## Baseline Metrics Template

Capture and store monthly snapshots:

1. Indexed pages count (`Page indexing` report).
2. Valid/invalid sitemap URLs.
3. Impressions and clicks by page type:
   - `/courses`
   - `/courses/{slug}` pages
4. Average CTR for top 20 course pages.
5. Average position for target course pages.
6. Top crawl errors and affected URLs.

## Release Checklist

Before deploy:

1. Run regression tests:
   - `php artisan test tests/Feature/SeoInfrastructureTest.php tests/Feature/PublicCoursePagesTest.php --compact`
2. Verify routes:
   - `/robots.txt` responds with sitemap reference.
   - `/sitemap.xml` includes current published courses only.
3. Verify metadata on one catalog page and one course page:
   - canonical
   - robots/googlebot
   - og/twitter tags
   - JSON-LD blocks

After deploy:

1. Validate live source on 2-3 public pages.
2. Validate sitemap endpoint and submit updated sitemap in Search Console if required.
3. Spot-check host canonical redirect behavior if enabled.

## Content Publishing Guidelines

For each new/updated course:

1. Title:
   - clear intent and human-readable.
2. SEO title override:
   - use only if needed for SERP clarity.
   - target ~60-70 chars.
3. SEO description override:
   - use value proposition + outcome.
   - target ~150-160 chars.
4. SEO image URL:
   - use a stable public URL.
5. Long description and requirements:
   - preserve heading structure and useful context.
6. Internal links:
   - confirm course is discoverable from catalog and related links render.

## Incident Handling

### If indexation drops unexpectedly

1. Confirm robots and canonical tags in live HTML.
2. Confirm sitemap includes affected pages.
3. Check host redirect and SSL/proxy config.
4. Check Search Console for manual action or crawl anomalies.

### If duplicate URLs appear in search

1. Validate canonical output for affected routes.
2. Ensure canonical host redirect is enabled in production (`SEO_ENFORCE_CANONICAL_HOST=true`).
3. Review route changes and slug redirect behavior.

## Environment Flags

- `SEO_ENFORCE_CANONICAL_HOST=false` by default.
- Enable in production when canonical host/protocol are stable.

## Public Analytics

1. Public-page analytics is runtime-configured via Branding settings.
2. In v1, analytics scripts render only on the public catalog and course-detail pages.
3. Use the `Rybbit` provider for a standard head script include with site ID.
4. Use `Custom` only when another provider requires a full custom head snippet.
5. Verify analytics does not appear on checkout, claim, learning, billing, profile, certificate, or admin pages.
