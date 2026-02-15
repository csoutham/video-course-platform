<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gift purchase confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Your gift purchase has been delivered.</p>

    <p>
        Course: {{ $giftPurchase->course->title ?? 'Course #'.$giftPurchase->course_id }}<br>
        Recipient: {{ $giftPurchase->recipient_email }}
    </p>

    <p>
        Recipient claim link:
        <a href="{{ $claimUrl }}">{{ $claimUrl }}</a>
    </p>

    @if ($giftPurchase->buyer_user_id)
        <p>
            Track gift status:
            <a href="{{ route('gifts.index') }}">{{ route('gifts.index') }}</a>
        </p>
    @endif
</body>
</html>

