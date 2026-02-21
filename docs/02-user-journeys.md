# 02. User Journeys

## Journey A: Visitor Browses Catalog

1. Visitor opens `/courses`.
2. Visitor views course cards, pricing, and summary metadata.
3. Visitor opens `/courses/{slug}` for details, curriculum preview, and purchase CTA.

Success criteria:

- Anonymous users can fully browse sellable courses.

## Journey B: Guest Checkout and Account Claim

1. Visitor clicks Buy on a course detail page.
2. App creates Stripe Checkout Session and redirects.
3. Visitor completes payment as guest.
4. Stripe sends `checkout.session.completed` webhook.
5. App creates/updates internal order in `paid` state.
6. App creates entitlement for purchased course.
7. App sends claim email tied to purchaser email.
8. User sets password via claim flow and gains account access.
9. Purchased course appears in `/my-courses`.

Success criteria:

- Entitlement persists correctly across guest-to-account claim transition.

## Journey C: Existing User Purchase

1. Authenticated user buys additional course.
2. Checkout completes.
3. Webhook finalizes order and grants entitlement.
4. New course appears in learner library.

Success criteria:

- Existing account receives incremental entitlements without duplicates.

## Journey D: Learning Experience

1. User opens `/my-courses`.
2. User selects entitled course.
3. User is routed to `/learn/{course}/{lesson?}`.
4. Course player loads module/lesson navigation and active lesson video.
5. Lesson summary is shown below the video for better scan/read flow and supports basic Markdown formatting.
6. Resources section is shown only when downloads exist.
7. User can access course-level, module-level, and lesson-level PDFs in playback.
8. User downloads resources via secure links.

Success criteria:

- Only entitled users can stream or download content.

## Journey E: Unauthorized Access

1. User directly visits protected lesson/resource URL without entitlement.
2. Authorization policy denies access.
3. User is redirected or shown clear access-denied state.

Success criteria:

- No protected media or files are leaked.

## Journey F: Gift Purchase and Recipient Claim

1. Buyer opens course page and enables `Gift this course`.
2. Buyer enters recipient details and optional message.
3. Buyer completes Stripe checkout.
4. Webhook marks order paid, creates gift record, and issues gift claim token.
5. Recipient receives gift email with claim link.
6. Recipient opens claim link and either logs in or creates account with recipient email.
7. Gift is marked claimed and entitlement is granted to recipient account.

Success criteria:

- Buyer does not receive entitlement for gift orders.
- Recipient receives entitlement only after successful claim.

## Journey G: Free Course Lead Magnet (Self Enroll)

1. Visitor opens a course marked as free.
2. Visitor submits enrollment form (or secure claim-link flow, based on course mode).
3. App creates zero-value local order (`free_*` session id) without Stripe checkout.
4. If `free_access_mode=direct` and user is authenticated:
   - entitlement is granted immediately.
5. Otherwise:
   - claim token is created and emailed.
   - user claims access through existing claim journey.

Success criteria:

- Free enrollment does not call Stripe.
- Entitlement behavior follows configured free mode.

## Journey H: Free Gift Distribution

1. Buyer selects `Gift this course` on a free course.
2. Buyer enters recipient details and optional message.
3. App creates zero-value local order and gift purchase record.
4. Gift claim token is issued and emailed to recipient.
5. Recipient claims and entitlement is granted.

Success criteria:

- Buyer receives no entitlement from free gift.
- Recipient receives entitlement only after claim.

## Payment Failure and Cancel Path

- If checkout is canceled, no entitlement is created.
- If async payment fails, order moves to failed and access is withheld.
- UI messaging directs users back to course detail page for retry.

## Refund and Revocation Path

- Refund is issued manually in Stripe Dashboard.
- Refund webhook sync marks order refunded.
- Entitlement is revoked based on configured policy.
- User loses access to protected lessons and resources.

## Journey I: Completion Certificate

1. Entitled learner completes 100% of published lessons in a certificate-enabled course.
2. Learner clicks `Download certificate` from `My Courses` or lesson playback.
3. App issues a certificate snapshot record (name/title/date/code) on first request.
4. App renders certificate PDF from the course template and overlays learner/course details.
5. Learner can share verification URL or code.
6. Any full refund revokes the certificate and verification reflects revoked status.

Success criteria:

- Certificate is issued once per learner/course and is publicly verifiable.
- Full refunds revoke certificate validity.
