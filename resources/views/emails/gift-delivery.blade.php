<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gift delivery</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>You received a gift course on {{ $branding?->platformName ?? config('app.name', 'Video Courses') }}.</p>

    <p>
        Course: {{ $giftPurchase->course->title ?? 'Course #'.$giftPurchase->course_id }}<br>
        From: {{ $giftPurchase->buyer_email }}
    </p>

    @if ($giftPurchase->gift_message)
        <p>Message from the buyer:</p>
        <blockquote style="margin: 0; padding: 12px; background: #f8fafc; border-left: 4px solid #cbd5e1;">
            {{ $giftPurchase->gift_message }}
        </blockquote>
    @endif

    <p>
        Claim your gift:
        <a href="{{ $claimUrl }}">{{ $claimUrl }}</a>
    </p>
</body>
</html>
