<?php

namespace App\Http\Controllers\Api\V1\Mobile\Concerns;

use Illuminate\Http\JsonResponse;

trait RespondsWithApiErrors
{
    protected function errorResponse(string $code, string $message, int $status, ?array $details = null): JsonResponse
    {
        $payload = [
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if ($details !== null) {
            $payload['error']['details'] = $details;
        }

        return response()->json($payload, $status);
    }
}
