# 25. Free Lead-Magnet Flow

## Summary

VideoCourses supports free course distribution without Stripe checkout.

This reuses existing order, claim-token, entitlement, and gift mechanics so paid and free delivery stay consistent.

## Course Configuration

- `is_free` (boolean)
  - `true`: checkout bypasses Stripe and creates zero-value local order.
  - `false`: standard paid Stripe checkout.
- `free_access_mode` (`claim_link|direct`)
  - `claim_link`: always issue purchase claim token.
  - `direct`: authenticated self-enroll grants entitlement immediately.

## Runtime Behavior

### Free self-enroll

1. User submits `POST /checkout/{course}` for free course.
2. System creates local order:
   - `status=paid`
   - `total_amount=0`
   - `stripe_checkout_session_id=free_{ulid}`
3. If direct mode + authenticated user:
   - entitlement granted immediately.
4. Else:
   - purchase claim token issued and claim URL attached to receipt email.

### Free gifting

1. Buyer selects gift flow with recipient details.
2. System creates local zero-value order and `gift_purchases` row.
3. Gift claim token issued.
4. Recipient gift email and buyer confirmation email sent.
5. Recipient claims gift and receives entitlement.

## UX Notes

- Course detail page shows `Free` pricing state.
- Promotion code input is hidden for free courses.
- Success page heading uses `Enrollment successful` for zero-value orders.

## Testing Coverage

- Free guest checkout creates claim token and sends receipt email.
- Free direct authenticated checkout grants entitlement without claim token.
- Free gift checkout creates gift purchase and sends gift emails.
- Paid checkout behavior remains unchanged.
