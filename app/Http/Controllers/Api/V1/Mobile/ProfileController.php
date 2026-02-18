<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Mobile\MobileUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new MobileUserResource($request->user()),
        ]);
    }
}
