<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'stripe_subscription_monthly_price_id',
    'stripe_subscription_yearly_price_id',
    'subscription_currency',
    'stripe_billing_portal_enabled',
    'stripe_billing_portal_configuration_id',
])]
class BillingSetting extends Model
{
    use HasFactory;

    #[\Override]
    protected function casts(): array
    {
        return [
            'stripe_billing_portal_enabled' => 'boolean',
        ];
    }
}
