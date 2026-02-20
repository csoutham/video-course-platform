# 24. Udemy URL Importer (No API)

## Goal

Allow admins to migrate course structure from Udemy into this platform by supplying only a public Udemy course URL.

## Scope

### In scope

- URL-only import flow in admin UI.
- Preview before commit.
- Upsert by source URL.
- Course metadata import.
- Curriculum shell import (module + lessons from public syllabus sections).
- Manual historical rating/review entry is supported separately from this importer on the Course Edit `Ratings and Reviews` tab.

### Out of scope

- Video file migration.
- Resource/download migration.
- Quizzes/assignments/exercises migration.
- Any private/authenticated scraping.
- Automatic import/scraping of Udemy review text or ratings from protected endpoints.

## Data Sources

The importer uses publicly available landing page data:

- JSON-LD (`script[type="application/ld+json"]`) as primary source.
- Meta tags and title as fallback.

## Import Mapping

### Course

- `source_platform`: `udemy`
- `source_url`: imported URL
- `source_external_id`: URL slug segment
- `title`: JSON-LD course name (fallback meta/title)
- `description`: JSON-LD description (fallback meta description)
- `thumbnail_url`: JSON-LD image (fallback og:image)
- `source_payload_json`: normalized snapshot used for import
- `source_last_imported_at`: commit timestamp

Defaults for new course records:

- `is_published=false`
- `price_amount=0`
- `price_currency=usd`

### Curriculum

- Modules are created from `syllabusSections`:
    - `title` from section name
    - `sort_order` from section position
    - `is_imported_shell=true`
    - `source_external_key` deterministic key
- Lessons are created from section children (`hasPart` or `itemListElement`) when available:
    - `title` from lesson name
    - `duration_seconds` from `timeRequired` when present
    - `is_imported_shell=true`
    - `source_external_key` deterministic key
    - `is_published=false`
    - `stream_video_id=null`
- Fallback behavior:
    - If lesson-level data is missing, one fallback lesson shell is created per imported module.

## Overwrite Modes

- `safe_merge` (default):
    - metadata only fills blank local fields.
    - imported lesson shells are upserted by key.
- `force_replace_metadata`:
    - overwrite title/description/thumbnail/source payload fields.
    - curriculum shell upsert remains non-destructive.
- `force_replace_curriculum`:
    - metadata safe-merge.
    - delete and rebuild imported shell lessons/module content.
- `full_replace_imported`:
    - replace metadata and imported curriculum shells.

## Compliance Guard

Preview and commit both require:

- `confirm_ownership=true`

This confirms the operator has rights to migrate/import the source content.

## Admin Routes

- `GET /admin/imports/udemy` → form + preview result
- `POST /admin/imports/udemy/preview` → parse URL and preview
- `POST /admin/imports/udemy/commit` → execute import/upsert

## Error Handling

- Invalid URL/host → validation error.
- Fetch blocked (anti-bot/challenge) → explicit parser/fetch error with manual fallback guidance.
- Missing structured curriculum → metadata-only preview/commit with warning.

## Cloudflare Challenge Fallback

Some Udemy pages return Cloudflare bot challenges (`HTTP 403`, `cf-mitigated: challenge`) for server-side requests.

When that happens:

1. Open the Udemy URL in your browser.
2. View page source.
3. Paste the full source into `HTML fallback (optional)` in admin import.
4. Run preview/commit again.

The importer will prefer pasted HTML over remote fetch when provided.

## Testing

- Preview validation and ownership guard.
- Successful preview parsing from JSON-LD.
- Commit creates course + imported module + lesson shells.
- Re-import by same URL does not duplicate shell lessons.
- Preview works with manual HTML fallback when remote fetch is challenged.
- Overwrite mode path tests (`force_replace_curriculum` at minimum).
