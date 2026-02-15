<?php

namespace App\Mail;

use App\Models\GiftPurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GiftDeliveryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly GiftPurchase $giftPurchase,
        public readonly string $claimUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You received a course gift');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.gift-delivery');
    }

    public function attachments(): array
    {
        return [];
    }
}

