# 05. Customer Routes and Livewire Contracts

## Public Routes

- `GET /`
  - Redirects to the course catalog (`/courses`).
- `GET /courses`
  - Catalog list and filters.
- `GET /courses/{slug}`
  - Course detail and buy CTA.
- `POST /checkout/{course}`
  - Creates Stripe Checkout Session.
- `GET /checkout/success`
  - User-facing confirmation state pending webhook finalization.
- `GET /checkout/cancel`
  - Payment canceled message and retry CTA.
- `GET /claim-purchase`
  - Claim account from purchase email token.

## Authenticated Routes

- `GET /my-courses`
  - List of user’s active entitlements.
- `GET /learn/{course}/{lesson?}`
  - Course player; defaults to next incomplete lesson if lesson omitted.
- `POST /learn/{course}/{lesson}/progress/complete`
  - Mark lesson progress as completed for entitled learner.
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
  - Inputs: course ID, optional coupon code.
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
- Player and download routes require auth plus active entitlement.
- Authorization policy checks user-course entitlement on every protected request.

## UX State Requirements

- Loading states for all asynchronous UI sections.
- Empty states for no courses and no entitlements.
- Error states for access denied, failed checkout, and unavailable lesson/resource.
