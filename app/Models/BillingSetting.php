<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'stripe_subscription_monthly_price_id',
        'stripe_subscription_yearly_price_id',
        'subscription_currency',
        'stripe_billing_portal_enabled',
        'stripe_billing_portal_configuration_id',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'stripe_billing_portal_enabled' => 'boolean',
        ];
    }
}
