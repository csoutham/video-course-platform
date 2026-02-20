<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Preorders\PreorderCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PreorderCheckoutController extends Controller
{
    public function __invoke(Request $request, Course $course, PreorderCheckoutService $preorderCheckoutService): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email'],
        ]);

        $customerEmail = $request->user()?->email ?? ($validated['email'] ?? null);

        if (! $customerEmail) {
            return back()->withErrors([
                'email' => 'Email is required for preorder checkout.',
            ])->withInput();
        }

        try {
            $session = $preorderCheckoutService->createCheckoutSession($course, $request->user(), $customerEmail);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'preorder' => $exception->getMessage(),
            ])->withInput();
        }

        return redirect()->away((string) ($session['url'] ?? ''));
    }
}
