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
5. User downloads lesson resources via secure links.

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

## Payment Failure and Cancel Path

- If checkout is canceled, no entitlement is created.
- If async payment fails, order moves to failed and access is withheld.
- UI messaging directs users back to course detail page for retry.

## Refund and Revocation Path

- Refund is issued manually in Stripe Dashboard.
- Refund webhook sync marks order refunded.
- Entitlement is revoked based on configured policy.
- User loses access to protected lessons and resources.
