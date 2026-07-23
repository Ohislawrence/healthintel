<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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

    /**
     * Send password reset link (forgot password).
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Don't reveal whether the email exists
        $message = 'If that email exists, a password reset token has been generated.';

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->success(null, $message);
        }

        // Generate a 6-digit token (mobile-friendly)
        $token = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store token in password_reset_tokens table
        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $token, 'created_at' => now()]
        );

        return $this->success([
            'token' => $token,
        ], $message);
    }

    /**
     * Reset password using token.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(null, 'Password has been reset successfully. You can now log in.');
        }

        return $this->error('Invalid or expired reset token.', 422);
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
