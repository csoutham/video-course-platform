# Kit Purchaser Sync

## Goal

Sync course purchasers into Kit (formerly ConvertKit) so marketing automations can run from tags and tag-based segments.

## Behavior

- Sync runs after an order first transitions to `paid`.
- Sync runs for Stripe and free checkout flows.
- Gift purchases sync the buyer email (the purchaser), not the gift recipient.
- Failures are non-blocking and logged as `kit_sync_failed`.

## Configuration

Add to `.env`:

```dotenv
KIT_ENABLED=false
KIT_API_KEY=
KIT_API_BASE_URL=https://api.kit.com/v4
KIT_PURCHASER_TAG_IDS=
KIT_COURSE_TAG_MAP={}
```

## Tag Strategy

Two tag sources are supported:

1. Global purchaser tags from `KIT_PURCHASER_TAG_IDS` (comma-separated IDs).
2. Course-specific tags from:
   - Course admin field: `Kit tag ID`
   - Optional fallback map in `KIT_COURSE_TAG_MAP` JSON:
     - `{"course:12": 12345, "my-course-slug": 67890}`

## API Calls

On successful paid order:

1. `POST /subscribers` with purchaser email.
2. `POST /tags/{tagId}/subscribers` for each resolved tag.

## Notes

- Kit “segments” are typically built in Kit UI from tag rules; this integration writes tags.
- Keep tag IDs numeric and managed in Kit before enabling sync in production.
