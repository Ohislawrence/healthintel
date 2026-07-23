<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController
{
    public function __construct(
        private CreditService $creditService,
    ) {}

    /**
     * Register a new user + grant signup credits. Returns a Sanctum token.
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('user');

        // Grant free signup credits
        $this->creditService->grantSignupCredits($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => $this->formatUser($user),
            'token' => $token,
        ], 'Account created successfully', 201);
    }

    /**
     * Login — returns a Sanctum token.
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials', 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => $this->formatUser($user),
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * Get the authenticated user (with health profile + credits loaded).
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('healthProfile');

        return $this->success([
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Logout — revoke the current token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out');
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'phone_verified_at' => $user->phone_verified_at,
            'credits' => $this->creditService->getBalance($user),
            'roles' => $user->getRoleNames()->toArray(),
            'health_profile' => $user->healthProfile ? [
                'date_of_birth' => $user->healthProfile->date_of_birth?->toDateString(),
                'sex' => $user->healthProfile->sex,
                'is_pregnant' => $user->healthProfile->is_pregnant,
                'height_cm' => $user->healthProfile->height_cm,
                'weight_kg' => $user->healthProfile->weight_kg,
                'blood_type' => $user->healthProfile->blood_type,
                'medical_conditions' => $user->healthProfile->medical_conditions,
                'current_medications' => $user->healthProfile->current_medications,
                'profile_completed' => $user->healthProfile->profile_completed,
            ] : null,
            'created_at' => $user->created_at,
        ];
    }
}
