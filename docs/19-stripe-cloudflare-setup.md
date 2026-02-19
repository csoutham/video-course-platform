# 19. Stripe + Cloudflare Setup (Local to Production)

This is the concrete setup checklist to get checkout, claim flow, video playback, and resource downloads working
reliably.

## 1) Required Environment Variables

Set these in `.env`:

```dotenv
APP_URL=http://127.0.0.1:8000

STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

CF_STREAM_IFRAME_BASE_URL=https://iframe.videodelivery.net
CF_STREAM_SIGNED_URLS_ENABLED=false
CF_STREAM_TOKEN_TTL_SECONDS=3600
CF_STREAM_ACCOUNT_ID=
CF_STREAM_API_TOKEN=
CF_STREAM_CUSTOMER_CODE=

COURSE_RESOURCES_DISK=s3
IMAGE_UPLOAD_DISK=s3
AWS_ACCESS_KEY_ID=<r2-access-key-id>
AWS_SECRET_ACCESS_KEY=<r2-secret>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=<r2-bucket-name>
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_URL=https://<public-r2-domain-or-cdn>

MAIL_MAILER=log
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME="Video Courses"
GIFTS_ENABLED=false
```

Notes:

- `STRIPE_WEBHOOK_SECRET` must match the currently active endpoint secret from Stripe CLI or Stripe Dashboard.
- `COURSE_RESOURCES_DISK=s3` is required for Cloudflare R2-backed resource downloads.
- `IMAGE_UPLOAD_DISK=s3` is required for image uploads (course thumbnails + branding logos) to use Cloudflare R2.
- `CF_STREAM_IFRAME_BASE_URL` can remain the default unless you have a custom delivery domain.
- For signed Stream playback, configure `CF_STREAM_*` signing values and enable signed URLs in Cloudflare Stream.
- `MAIL_MAILER` must be configured in each environment to deliver purchase receipt emails.
- Set `GIFTS_ENABLED=true` only when gift flows are ready for production use.

## 2) Stripe Product/Price Mapping (Critical)

A course is purchasable only when `courses.stripe_price_id` is populated.

If missing, checkout returns:

- `Course is not purchasable yet.`

### Option A: Create via admin (recommended)

1. Open `/admin/courses/create`.
2. Enter course fields and keep `Auto-create Stripe price` enabled.
3. Save course and verify `stripe_price_id` exists on `/admin/courses/{course}/edit`.
4. Publish course when ready.

### Option B: Create product and one-time price in Stripe Dashboard (manual)

1. Create Product for each course.
2. Add one-time Price in USD.
3. Copy the Stripe Price ID (`price_...`).
4. Save it into the matching course row.

Example via `php artisan tinker`:

```php
$course = App\Models\Course::where('slug', 'foundations-of-product-ops')->first();
$course->stripe_price_id = 'price_1234567890';
$course->save();
```

Verify mapping:

```bash
php artisan tinker --execute="App\\Models\\Course::query()->select('id','slug','stripe_price_id','is_published')->get();"
```

## 3) Local Webhook Wiring

Run app:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

In another terminal, forward Stripe events:

```bash
stripe listen --forward-to http://127.0.0.1:8000/webhooks/stripe
```

Copy the reported `whsec_...` value to `.env` as `STRIPE_WEBHOOK_SECRET`, then clear config cache:

```bash
php artisan config:clear
```

Recommended events for this app:

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`
- `checkout.session.async_payment_failed`
- `charge.refunded`

## 4) Cloudflare Stream Setup

Per lesson, set `course_lessons.stream_video_id` to the Stream video UID.

Expected embed URL shape:

- `https://iframe.videodelivery.net/<video_uid>`

Quick data check:

```bash
php artisan tinker --execute="App\\Models\\CourseLesson::query()->select('id','course_id','slug','stream_video_id')->get();"
```

Sync stored lesson durations from Cloudflare Stream metadata:

```bash
php artisan videocourses:stream-sync-durations
```

Admin workflow:

- In `/admin/courses/{course}/edit`, add/edit lessons and choose Stream videos directly from uploaded assets.
- When a Stream video is selected and lesson is saved, admin flow automatically enforces Stream signed URLs and syncs
  duration metadata.

### Optional Stream hardening with signed URLs

1. In Cloudflare Stream, enable signed URL requirement for private delivery.
2. Create Stream API token with permissions needed to create playback tokens.
3. Set:
    - `CF_STREAM_SIGNED_URLS_ENABLED=true`
    - `CF_STREAM_ACCOUNT_ID`
    - `CF_STREAM_API_TOKEN`
    - `CF_STREAM_CUSTOMER_CODE`
    - `CF_STREAM_TOKEN_TTL_SECONDS` (for example `900`)
4. Clear config cache:

```bash
php artisan config:clear
```

## 5) Cloudflare R2 Setup

1. Create R2 bucket(s) for resources and image uploads.
2. Create API token (Object Read/Write as needed).
3. Use token as `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY`.
4. Set `AWS_ENDPOINT` to account endpoint.
5. Set `AWS_URL` to your public R2 custom domain/CDN URL so uploaded image URLs resolve publicly.
6. Set resource rows with `disk='s3'`, object key in `path`.

Quick check:

```bash
php artisan tinker --execute="App\\Models\\LessonResource::query()->select('id','lesson_id','disk','path')->get();"
```

## 6) End-to-End Test Recipe

1. Ensure course has `is_published=1` and valid `stripe_price_id`.
2. Open course page as guest, start checkout with email.
3. Complete Stripe test payment.
4. Land on `/checkout/success?session_id=...`.
5. Confirm page shows `Claim your purchase`.
6. Complete claim flow.
7. Confirm access appears under `My Courses`.
8. Confirm receipt email is delivered with Stripe receipt link, and guest purchases include the claim link.
9. (If gifts enabled) run a gift checkout and confirm recipient + buyer gift emails are delivered.

If payment succeeded but no claim button appears:

- Wait 3-10 seconds and click `Refresh status` on success page.
- Check `stripe_events` and `orders` rows for that `session_id`.
- Replay event with:

```bash
php artisan videocourses:stripe-reprocess <stripe_event_id>
```

## 7) Production Webhook and App URL Checklist

Before production cutover:

1. Set production `APP_URL` to real domain.
2. Configure Stripe webhook endpoint to `https://<your-domain>/webhooks/stripe`.
3. Use production `sk_live` / `pk_live` / webhook secret.
4. Ensure queue worker is running for any async side effects.
5. Validate one real payment in low-value test product.

## 8) Quick SQL for Debugging Purchase State

```sql
-- Find most recent orders
select id, email, stripe_checkout_session_id, user_id, status, paid_at
from orders
order by id desc
limit 20;

-- Find guest claim tokens
select pct.id, pct.order_id, pct.token, pct.expires_at, pct.consumed_at
from purchase_claim_tokens pct
join orders o on o.id = pct.order_id
order by pct.id desc
limit 20;

-- Find webhook processing entries
select stripe_event_id, event_type, processed_at, processing_error
from stripe_events
order by id desc
limit 20;
```
