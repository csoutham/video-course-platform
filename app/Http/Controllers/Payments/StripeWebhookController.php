<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Services\Payments\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeWebhookService $webhookService): JsonResponse
    {
        $signature = $request->header('Stripe-Signature');

        if (! $signature) {
            return response()->json(['message' => 'Missing Stripe signature.'], 400);
        }

        try {
            $webhookService->handle($request->getContent(), $signature);
        } catch (SignatureVerificationException|UnexpectedValueException) {
            return response()->json(['message' => 'Invalid webhook payload.'], 400);
        } catch (\Throwable $exception) {
            Log::error('stripe_webhook_processing_failed', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed.'], 500);
        }

        return response()->json(['ok' => true]);
    }
}
