# 19. Stripe + Cloudflare Setup (Local to Production)

This is the concrete setup checklist to get checkout, claim flow, video playback, and resource downloads working reliably.

## 1) Required Environment Variables

Set these in `.env`:

```dotenv
APP_URL=http://127.0.0.1:8000

STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

CF_STREAM_IFRAME_BASE_URL=https://iframe.videodelivery.net

COURSE_RESOURCES_DISK=s3
AWS_ACCESS_KEY_ID=<r2-access-key-id>
AWS_SECRET_ACCESS_KEY=<r2-secret>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=<r2-bucket-name>
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Notes:
- `STRIPE_WEBHOOK_SECRET` must match the currently active endpoint secret from Stripe CLI or Stripe Dashboard.
- `COURSE_RESOURCES_DISK=s3` is required for Cloudflare R2-backed resource downloads.
- `CF_STREAM_IFRAME_BASE_URL` can remain the default unless you have a custom delivery domain.

## 2) Stripe Product/Price Mapping (Critical)

A course is purchasable only when `courses.stripe_price_id` is populated.

If missing, checkout returns:
- `Course is not purchasable yet.`

### Create product and one-time price in Stripe (Dashboard)

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

Per lesson, set `video_provider=cloudflare_stream` and store the Stream video UID in `video_source`.

Expected embed URL shape:
- `https://iframe.videodelivery.net/<video_uid>`

Quick data check:

```bash
php artisan tinker --execute="App\\Models\\Lesson::query()->select('id','slug','video_provider','video_source')->get();"
```

## 5) Cloudflare R2 Setup

1. Create private R2 bucket.
2. Create API token (Object Read/Write as needed).
3. Use token as `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY`.
4. Set `AWS_ENDPOINT` to account endpoint.
5. Set resource rows with `disk='s3'`, object key in `path`.

Quick check:

```bash
php artisan tinker --execute="App\\Models\\CourseResource::query()->select('id','disk','path')->get();"
```

## 6) End-to-End Test Recipe

1. Ensure course has `is_published=1` and valid `stripe_price_id`.
2. Open course page as guest, start checkout with email.
3. Complete Stripe test payment.
4. Land on `/checkout/success?session_id=...`.
5. Confirm page shows `Claim your purchase`.
6. Complete claim flow.
7. Confirm access appears under `My Courses`.

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
