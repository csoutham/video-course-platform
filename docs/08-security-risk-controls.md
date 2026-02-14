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
