# 05. Customer Routes and Livewire Contracts

## Public Routes

- `GET /`
  - Redirects to the course catalog (`/courses`).
- `GET /courses`
  - Catalog list and filters.
- `GET /courses/{slug}`
  - Course detail and buy CTA.
- `POST /checkout/{course}`
  - Paid courses: creates Stripe Checkout Session for self-purchase or gift purchase.
  - Free courses: creates zero-value local order and routes to success/claim flow.
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

## Authenticated Routes

- `GET /my-courses`
  - List of user’s active entitlements.
- `GET /gifts`
  - List of gifts purchased by current authenticated user.
- `GET /receipts`
  - List of user's Stripe-paid order receipts (non-zero, Stripe-backed only).
- `GET /receipts/{order:public_id}`
  - Redirect to Stripe-hosted receipt for an eligible Stripe order (owner-only).
- `GET /learn/{course}/{lesson?}`
  - Course player; defaults to next incomplete lesson if lesson omitted.
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
  - Outputs: overview, curriculum preview, purchase CTA.
- `CheckoutButton`
  - Inputs: course ID, optional coupon code, optional gift recipient fields.
  - Action: create Stripe session and redirect.
- `MyCourses`
  - Inputs: authenticated user.
  - Outputs: entitled course library.
- `CoursePlayer`
  - Inputs: course + lesson context.
  - Outputs: lesson navigation, active lesson media state, resource links.

## Authorization Rules

- Catalog/detail are public.
- Checkout is public but validates course publish/purchasable state.
- Checkout supports both paid and free enrollment paths from same endpoint.
- Player and download routes require auth plus active entitlement.
- Authorization policy checks user-course entitlement on every protected request.

## UX State Requirements

- Loading states for all asynchronous UI sections.
- Empty states for no courses and no entitlements.
- Error states for access denied, failed checkout, and unavailable lesson/resource.
