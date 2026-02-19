# Mobile Architecture

## Goals

- Provide a first-party mobile playback experience for existing customers.
- Reuse backend authorization and entitlement logic already present in the Laravel app.
- Keep the open-source implementation reproducible without paid framework dependencies.

## Architecture

- Backend: Laravel API v1 mobile surface (`/api/v1/mobile/*`).
- Auth: Sanctum personal access tokens (Bearer tokens).
- Mobile client: React Native + Expo app in the separate repo:
  `git@github.com:csoutham/video-course-platform-mobile.git`.
- Video delivery: Cloudflare Stream URL from backend playback endpoint.
- Resource delivery: signed URL returned by API, opened by mobile app.

## API Flow

1. User logs in with email/password and receives Bearer token.
2. App loads `/library` to show entitled courses.
3. App loads `/courses/{slug}` for course outline and progress state.
4. App loads `/playback` endpoint for selected lesson and stream URL.
5. App sends periodic `/progress` heartbeat updates during playback.
6. App requests `/resources/{id}` and opens signed download URL externally.

## Installation Model

Each mobile build is tied to one Video Courses installation via compile-time env variable:

- `EXPO_PUBLIC_API_BASE_URL`

This keeps per-owner/project deployments isolated and predictable.

## Error Contract

API errors use a stable shape:

```json
{
  "error": {
    "code": "forbidden",
    "message": "You are not allowed to perform this action.",
    "details": {}
  }
}
```

`details` is optional and mainly used for validation failures.

## V1 Constraints

- No in-app purchase flow.
- No offline video downloads.
- No multi-installation directory discovery.

## Operational Notes

- Production should keep Stream signing enabled.
- Rotate API keys and signing secrets per existing ops runbook.
- Use token revocation endpoints on support/security incidents.
