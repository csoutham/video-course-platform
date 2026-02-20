<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\BillingSettingsService;
use Illuminate\Contracts\View\View;

class EditController extends Controller
{
    public function __invoke(BillingSettingsService $billingSettingsService): View
    {
        return view('admin.billing.edit', [
            'settings' => $billingSettingsService->current(),
        ]);
    }
}
