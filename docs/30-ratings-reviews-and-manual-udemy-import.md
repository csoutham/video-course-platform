# 30. Ratings, Reviews, and Manual Udemy Review Import (v1.1 Plan)

## Summary

Add course ratings and reviews as a first-class customer feature, with a moderated learner flow and an admin workflow
to manually add historical Udemy reviews/ratings per course.

This design keeps trust and quality high by restricting learner submissions to entitled users with at least 25% course
progress, while allowing admin-entered Udemy reviews to be bulk imported and auto-approved.

## Locked Decisions

1. Review eligibility: only entitled users.
2. Moderation: native learner reviews require manual approval.
3. Review model: one review per user per course, editable.
4. Content rule: rating required, text optional.
5. Progress gate: learner must reach 25% course progress before submitting.
6. Public surfaces: course catalog + course detail.
7. Sort order: newest approved reviews first.
8. Edit rule: editing an approved learner review returns it to pending.
9. API scope: web-only in v1 (mobile API deferred).
10. Udemy manual entry: use the same reviews table, auto-approved.
11. Udemy manual entries: included in public aggregate rating/count and structured data.
12. Udemy manual input UX: per-course bulk paste + editable table.
13. Original Udemy review date: stored when available.
14. Public labeling: no source badge for imported reviews.

## Scope

### In scope

1. Native learner rating/review submission.
2. Admin moderation queue and actions.
3. Admin manual Udemy review import workflow.
4. Public aggregate rating/count display.
5. Structured data updates (`aggregateRating` + review nodes).
6. Pest test coverage for all new flows.

### Out of scope

1. Auto-scraping Udemy reviews from protected pages.
2. Helpful votes, threaded replies, abuse-report workflows.
3. Mobile API review endpoints.

## Data Model

### New table: `course_reviews`

- `id`
- `course_id` (FK, indexed)
- `user_id` (nullable FK, indexed)
- `source` (`native|udemy_manual`, indexed)
- `reviewer_name` (nullable string 120; required for `udemy_manual`)
- `rating` (tinyint unsigned, `1..5`, required)
- `title` (nullable string 120)
- `body` (nullable text)
- `status` (`pending|approved|rejected|hidden`, indexed)
- `original_reviewed_at` (nullable timestamp)
- `last_submitted_at` (nullable timestamp)
- `approved_at` (nullable timestamp)
- `approved_by_user_id` (nullable FK users)
- `rejected_at` (nullable timestamp)
- `rejected_by_user_id` (nullable FK users)
- `hidden_at` (nullable timestamp)
- `hidden_by_user_id` (nullable FK users)
- `moderation_note` (nullable text)
- timestamps

Constraints and indexes:

1. Unique: (`course_id`, `user_id`) for native single-review behavior.
2. Composite: (`course_id`, `status`, `approved_at`).
3. Composite: (`source`, `status`, `updated_at`).

### Extend `courses` with aggregate cache fields

- `reviews_approved_count` unsigned int default `0`
- `rating_average` decimal(3,2) nullable
- `rating_distribution_json` nullable json

## Eligibility and Rules

## Native submission eligibility

User can submit native review only when:

1. authenticated,
2. has active course access via existing access service,
3. course is published,
4. progress for that course is at least 25%.

### Progress calculation

1. Gather all published lessons for the course.
2. For each lesson use `LessonProgress.percent_complete` (or `0` when missing).
3. `course_progress_percent = floor(sum(percent_complete) / lesson_count)`.
4. Eligible when `course_progress_percent >= 25`.

### Moderation transitions

1. Native create/update -> `pending`.
2. Admin approve -> `approved` with moderator + timestamp.
3. Admin reject -> `rejected` with moderator + timestamp.
4. Admin hide -> `hidden`.
5. Admin unhide -> `approved`.
6. Native edit of approved review -> back to `pending`.
7. Udemy manual import create -> `approved` immediately.

### Aggregation

1. Only `approved` reviews are included in aggregate average/count/distribution.
2. Recompute aggregates on approve/reject/hide/unhide/import/delete/edit.
3. Persist aggregates on `courses` for fast catalog/detail reads.

## Public Interfaces and Routes

### Customer routes

1. `POST /courses/{course:slug}/reviews`
    - create or update current user review.
2. `DELETE /courses/{course:slug}/reviews`
    - delete current user review.

### Admin routes

1. `GET /admin/reviews`
    - moderation queue with filters.
2. `POST /admin/reviews/{review}/approve`
3. `POST /admin/reviews/{review}/reject`
4. `POST /admin/reviews/{review}/hide`
5. `POST /admin/reviews/{review}/unhide`
6. `POST /admin/courses/{course}/reviews/import/preview`
    - parse bulk-pasted Udemy review text.
7. `POST /admin/courses/{course}/reviews/import/commit`
    - save selected edited rows as approved manual reviews.

## Services and Responsibilities

1. `CourseReviewEligibilityService`
    - determine submit eligibility + reason + progress percent.
2. `CourseReviewService`
    - native upsert and delete flow.
3. `CourseReviewModerationService`
    - moderation actions and metadata updates.
4. `CourseReviewImportService`
    - parse/normalize preview rows and commit approved manual rows.
5. `CourseRatingAggregateService`
    - recalculate and persist course rating cache fields.

## UI Plan

### Catalog

1. Show `rating_average` and `reviews_approved_count` on course cards.
2. If no approved reviews, show neutral fallback.

### Course detail

1. Show aggregate rating summary and distribution.
2. Show approved review list (newest first).
3. Show learner review submission/edit card with clear state messaging:
    - insufficient progress,
    - pending moderation,
    - rejected awaiting resubmission,
    - editable review form.

### Admin moderation queue

1. Dedicated `/admin/reviews` screen.
2. Filter by status, source, course, rating, and submitter.
3. Quick moderation actions per review.

### Admin manual Udemy entry

1. Add Reviews tab in course edit.
2. Bulk paste box for raw review lines/CSV/TSV.
3. Parse preview into editable table.
4. Commit parsed rows in batch as approved `udemy_manual` entries.
5. Allow edit/delete of imported rows from the same admin surface.

## SEO and Structured Data

Update course JSON-LD output:

1. include `aggregateRating` when approved count > 0;
2. include a bounded set of recent approved `Review` nodes.

## Testing Plan (Pest)

### Feature tests

1. non-entitled users cannot submit.
2. entitled users below 25% cannot submit.
3. entitled users >=25% can submit rating-only and rating+text.
4. one review per user per course is enforced.
5. editing approved native review returns to pending.
6. admin moderation access is protected.
7. moderation transitions update visibility and aggregates.
8. manual Udemy import preview parses and reports invalid rows.
9. manual import commit creates approved rows and updates aggregates.
10. catalog/detail render only approved reviews.
11. structured data contains aggregate when available.
12. existing checkout/learning/gifts/subscriptions/preorders regressions remain green.

### Unit tests

1. progress eligibility calculation edge cases.
2. aggregate recalculation correctness.
3. manual import parser normalization rules.

## Rollout

1. Deploy schema + services + tests with `REVIEWS_ENABLED=false`.
2. Enable admin moderation and manual import UI.
3. Enable public display and learner submission UI.
4. Stage validation with seeded/manual data.
5. Production enable + monitor moderation queue and aggregate integrity.

