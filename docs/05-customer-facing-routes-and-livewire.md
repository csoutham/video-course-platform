# 05. Customer Routes and Livewire Contracts

## Public Routes

- `GET /`
  - Redirects to the course catalog (`/courses`).
- `GET /courses`
  - Catalog list and filters.
- `GET /courses/{slug}`
  - Course detail and buy CTA.
- `POST /courses/{course:slug}/reviews`
  - Auth-only learner review submission/update (rating required, text optional).
- `DELETE /courses/{course:slug}/reviews`
  - Auth-only learner review deletion.
- `POST /checkout/{course}`
  - Paid courses: creates Stripe Checkout Session for self-purchase or gift purchase.
  - Free courses: creates zero-value local order and routes to success/claim flow.
- `POST /checkout/subscription`
  - Auth-only subscription checkout start (`monthly|yearly`) via Stripe Checkout.
- `POST /preorder/{course}`
  - Preorder setup checkout (`mode=setup`) to reserve payment method for release-time charge.
- `GET /checkout/success`
  - User-facing confirmation state pending webhook finalization.
- `GET /checkout/cancel`
  - Payment canceled message and retry CTA.
- `GET /claim-purchase/{token}`
  - Claim account from purchase email token.
- `GET /gift-claim/{token}`
  - Claim gifted course from recipient claim token.
- `POST /gift-claim/{token}`
  - Redeem gifted course into recipient account.
- `GET /certificates/verify/{code}`
  - Public certificate verification endpoint.

## Authenticated Routes

- `GET /my-courses`
  - List of user’s active entitlements.
- `GET /gifts`
  - List of gifts purchased by current authenticated user.
- `GET /billing`
  - Subscription status and preorder payment issue recovery panel.
- `POST /billing/portal`
  - Redirects to Stripe Billing Portal session when enabled.
- `GET /receipts`
  - List of user's Stripe-paid order receipts (non-zero, Stripe-backed only).
- `GET /receipts/{order:public_id}`
  - Redirect to Stripe-hosted receipt for an eligible Stripe order (owner-only).
- `GET /learn/{course}/{lesson?}`
  - Course player; defaults to next incomplete lesson if lesson omitted.
- `GET /my-courses/{course:slug}/certificate`
  - Streams generated completion certificate PDF for eligible learner.
- `POST /learn/{course}/{lesson}/progress/complete`
  - Toggle lesson progress between completed and in-progress for entitled learner.
- `GET /resources/{resource}/download`
  - Authorize then redirect to signed R2 URL.

## Livewire Components

- `CourseCatalog`
  - Inputs: filters/sort/search.
  - Outputs: paginated course cards.
- `CourseDetail`
  - Inputs: course slug.
  - Outputs: overview, curriculum preview, purchase CTA, rating summary, approved reviews, learner review form.
- `CheckoutButton`
  - Inputs: course ID, optional coupon code, optional gift recipient fields.
  - Action: create Stripe session and redirect.
- `MyCourses`
  - Inputs: authenticated user.
  - Outputs: entitled course library.
- `CoursePlayer`
  - Inputs: course + lesson context.
  - Outputs: lesson navigation, active lesson media state, resource links.
- `Certificate Download`
  - Inputs: authenticated user + course slug.
  - Outputs: generated PDF when access + completion checks pass.

## Authorization Rules

- Catalog/detail are public.
- Checkout is public but validates course publish/purchasable state.
- Checkout supports both paid and free enrollment paths from same endpoint.
- Review submission requires enabled reviews feature, active course access, and minimum progress threshold.
- Subscription checkout requires authenticated user.
- Standard checkout is blocked for unreleased preorder courses.
- Player and download routes require auth plus active entitlement.
- Certificate download requires auth, active access, course certificate enabled/template configured, and 100% lesson completion.
- Authorization policy checks user-course entitlement or active subscription (when enabled) on protected learning requests.

## UX State Requirements

- Loading states for all asynchronous UI sections.
- Empty states for no courses and no entitlements.
- Error states for access denied, failed checkout, and unavailable lesson/resource.
