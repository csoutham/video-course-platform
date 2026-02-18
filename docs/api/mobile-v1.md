# API: Mobile v1

Base path: `/api/v1/mobile`

## Auth

### `POST /auth/login`
Request:

```json
{
  "email": "user@example.com",
  "password": "password",
  "device_name": "iPhone 16"
}
```

Response:

```json
{
  "token": "<plain-text-token>",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Chris",
    "email": "user@example.com",
    "updated_at": "2026-02-18T08:00:00+00:00"
  }
}
```

### `POST /auth/logout`
Auth required. Revokes current token.

### `POST /auth/logout-all`
Auth required. Revokes all tokens for current user.

## Profile

### `GET /me`
Auth required.

## Library

### `GET /library`
Auth required.

Returns entitled, published courses with progress summary.

## Course Detail

### `GET /courses/{courseSlug}`
Auth required.

Returns course metadata, modules, lessons, lesson resources, and per-lesson progress.

## Playback

### `GET /courses/{courseSlug}/lessons/{lessonSlug}/playback`
Auth required.

Response includes:

- `stream_url`
- `heartbeat_seconds`
- `auto_complete_percent`
- `lesson`
- `progress`

### `POST /courses/{courseSlug}/lessons/{lessonSlug}/progress`
Auth required.

Request:

```json
{
  "position_seconds": 120,
  "duration_seconds": 300,
  "is_completed": false
}
```

Response:

```json
{
  "status": "in_progress",
  "percent_complete": 40,
  "playback_position_seconds": 120,
  "updated_at": "2026-02-18T08:00:00+00:00"
}
```

## Resources

### `GET /resources/{resourceId}`
Auth required.

Returns short-lived signed URL to file endpoint.

### `GET /resources/{resourceId}/file`
Signed URL endpoint. No bearer token required when signature is valid.

## Error Contract

All API errors follow:

```json
{
  "error": {
    "code": "validation_failed",
    "message": "The provided data is invalid.",
    "details": {
      "field": ["message"]
    }
  }
}
```

`details` is optional.
