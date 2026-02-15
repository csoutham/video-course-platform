<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase receipt</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Thanks for your purchase.</p>

    <p>
        Order #{{ $order->id }}<br>
        Total: {{ strtoupper($order->currency) }} {{ number_format($order->total_amount / 100, 2) }}<br>
        Paid: {{ optional($order->paid_at)->format('M d, Y H:i') ?? now()->format('M d, Y H:i') }}
    </p>

    @if ($order->items->isNotEmpty())
        <p>Courses purchased:</p>
        <ul>
            @foreach ($order->items as $item)
                <li>{{ $item->course->title ?? 'Course #'.$item->course_id }}</li>
            @endforeach
        </ul>
    @endif

    @if ($stripeReceiptUrl)
        <p><a href="{{ $stripeReceiptUrl }}">View Stripe receipt</a></p>
    @endif

    @if ($claimUrl)
        <p>
            You checked out as a guest. Claim this purchase to unlock your course access:
            <a href="{{ $claimUrl }}">{{ $claimUrl }}</a>
        </p>
    @else
        <p>
            Access your courses here:
            <a href="{{ route('my-courses.index') }}">{{ route('my-courses.index') }}</a>
        </p>
    @endif
</body>
</html>

