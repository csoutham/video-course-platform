# 08. Security and Risk Controls

## Primary Risks

- Unauthorized lesson playback.
- Unauthorized file download via shared links.
- Stripe webhook spoofing.
- Webhook replay causing duplicate grants.
- Sensitive key leakage in app/runtime config.

## Controls

### Access Control

- Auth middleware on learner and download routes.
- Policy checks for active entitlement before serving protected content.
- Sanctum token auth on `/api/v1/mobile/*` endpoints with scoped token abilities.
- Route throttles for sensitive flows:
    - checkout start
    - purchase claim submit
    - gift claim submit
    - Stripe webhook endpoint

### Payment Integrity

- Strict Stripe webhook signature verification.
- Reject events missing valid signature.

### Replay and Idempotency

- Persist unique Stripe event IDs.
- No-op on duplicate deliveries.
- Deterministic order and entitlement transitions.

### Protected Downloads

- Private R2 bucket.
- Short-lived signed URLs.
- Signed URLs generated only after entitlement authorization.

### Secrets Management

- Keep Stripe, Stream, and R2 credentials in environment secrets.
- Rotate secrets on a defined schedule and incident response triggers.

### Response Hardening

- Baseline security response headers on web responses:
    - `X-Content-Type-Options: nosniff`
    - `X-Frame-Options: SAMEORIGIN`
    - `Referrer-Policy: strict-origin-when-cross-origin`
    - `Permissions-Policy` restrictive baseline
    - `X-XSS-Protection: 0`
- HSTS enabled for secure requests in production-like deployments.

## Audit Logging

Capture and retain logs for:

- Checkout session creation attempts.
- Webhook processing outcomes.
- Entitlement grants/revocations.
- Resource download authorization attempts.

## Security Validation Checklist

- Verify unauthorized user cannot access any lesson route.
- Verify direct resource URL reuse fails after expiry.
- Verify webhook endpoint rejects invalid signatures.
- Verify duplicate event IDs do not alter state.
