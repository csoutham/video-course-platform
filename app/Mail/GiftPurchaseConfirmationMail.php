<?php

namespace App\Mail;

use App\Models\GiftPurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GiftPurchaseConfirmationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly GiftPurchase $giftPurchase,
        public readonly string $claimUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your gift purchase confirmation');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.gift-purchase-confirmation');
    }

    public function attachments(): array
    {
        return [];
    }
}
