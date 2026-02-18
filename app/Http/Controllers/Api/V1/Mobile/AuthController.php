<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Api\V1\Mobile\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Mobile\MobileUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use RespondsWithApiErrors;

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:120'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->errorResponse(
                code: 'invalid_credentials',
                message: 'The provided credentials are incorrect.',
                status: 422
            );
        }

        $token = $user->createToken($validated['device_name'], [
            'mobile:read',
            'mobile:progress:write',
            'mobile:auth',
        ])->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new MobileUserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all devices.',
        ]);
    }
}
