# 27. Mobile Playback App Strategy (Per Installation)

## Summary

VideoCourses now supports a dedicated mobile API and an Expo-based React Native client path for iOS and Android. The mobile app is scoped to playback for already-purchased courses and binds to one installation at build time.

## Decisions

- Mobile stack: React Native + Expo.
- API auth: Laravel Sanctum personal access tokens.
- Purchases: web checkout only for v1.
- Offline video: out of scope for v1.
- Deployment model: one compiled app per owner/project installation.

## Delivered Scope

1. Mobile API endpoints in `routes/api.php` under `/api/v1/mobile`.
2. Token login/logout/logout-all and profile endpoint.
3. Entitled library endpoint with progress summary.
4. Course detail endpoint with modules, lessons, resources, and per-lesson progress.
5. Playback endpoint returning Stream URL, lesson context, and progress state.
6. Progress heartbeat endpoint.
7. Resource endpoint returning short-lived signed URL.
8. Signed file endpoint for browser/download handoff.
9. Standard API error contract: `{ error: { code, message, details? } }`.
10. Mobile API feature tests.

## Security Model

- Entitlement checks are enforced server-side on every protected endpoint.
- Sanctum tokens are ability-scoped (`mobile:read`, `mobile:progress:write`, `mobile:auth`).
- Resource URLs are signed and short-lived.
- Signed resource file endpoint re-validates user entitlement.
- API rate limiter (`mobile-api`) is user/IP keyed.

## Next Phases

1. Complete the mobile UI in `mobile/` for production polish and store packaging.
2. Add optional push notifications for release announcements and reminders.
3. Add stronger media hardening controls (e.g. stricter Stream token TTL tuning per environment).
4. Evaluate offline encrypted downloads as a post-v1 milestone.
