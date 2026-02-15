<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseReceiptMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly ?string $claimUrl = null,
        public readonly ?string $stripeReceiptUrl = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your VideoCourses receipt',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-receipt',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
