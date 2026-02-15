<?php

namespace App\Http\Controllers\Gifts;

use App\Http\Controllers\Controller;
use App\Models\GiftPurchase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MyGiftsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $gifts = GiftPurchase::query()
            ->with('course')
            ->where(function ($query) use ($user): void {
                $query->where('buyer_user_id', $user->id)
                    ->orWhere('buyer_email', $user->email);
            })->latest()
            ->get();

        return view('learning.my-gifts', [
            'gifts' => $gifts,
        ]);
    }
}
