<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\CreditService;
use App\Services\ReferralProgramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends BaseController
{
    public function __construct(
        private CreditService $creditService,
        private ReferralProgramService $referralService,
    ) {}

    /**
     * Register a new user. Sends a 4-digit verification code via email.
     * Does NOT return a token — user must verify email first.
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('user');

        // Generate 4-digit verification code
        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $user->update([
            'email_verification_code' => $code,
            'email_verification_code_expires_at' => now()->addMinutes(30),
        ]);

        // Send verification email
        \Mail::raw(
            "Your HealthIntel verification code is: {$code}\n\nThis code expires in 30 minutes.",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Your HealthIntel Verification Code');
            }
        );

        // Generate referral code for the new user
        $this->referralService->generateReferralCode($user);

        // Attach referrer if referral code was provided
        $refCode = $request->input('ref');
        if ($refCode) {
            $this->referralService->attachReferrer($user, $refCode);
        }

        // Grant free signup credits
        $this->creditService->grantSignupCredits($user);

        return $this->success([
            'user_id' => $user->id,
            'email' => $user->email,
        ], 'Verification code sent to your email', 201);
    }

    /**
     * Verify email with 4-digit code. Returns a Sanctum token on success.
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'code' => 'required|string|size:4',
        ]);

        $user = User::findOrFail($request->user_id);

        if (!$user->email_verification_code || !$user->email_verification_code_expires_at) {
            return $this->error('No verification code requested.', 400);
        }

        $expiresAt = $user->email_verification_code_expires_at;
        if (is_string($expiresAt)) {
            $expiresAt = new \Carbon\Carbon($expiresAt);
        }
        if ($expiresAt && $expiresAt->isPast()) {
            return $this->error('Verification code has expired. Request a new one.', 410);
        }

        if ($user->email_verification_code !== $request->code) {
            return $this->error('Invalid verification code.', 422);
        }

        // Mark email as verified
        $user->update([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Send welcome email with features overview and navigation guide
        app(\App\Services\EmailService::class)->sendWelcomeEmail($user);

        return $this->success([
            'user' => $this->formatUser($user),
            'token' => $token,
        ], 'Email verified successfully');
    }

    /**
     * Resend verification code.
     */
    public function resendVerificationCode(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->email_verified_at) {
            return $this->error('Email is already verified.', 400);
        }

        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $user->update([
            'email_verification_code' => $code,
            'email_verification_code_expires_at' => now()->addMinutes(30),
        ]);

        \Mail::raw(
            "Your HealthIntel verification code is: {$code}\n\nThis code expires in 30 minutes.",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Your HealthIntel Verification Code');
            }
        );

        return $this->success(null, 'New verification code sent');
    }

    /**
     * Google Sign-In / Sign-Up.
     * Validates the Google ID token, creates or finds the user,
     * and returns a Sanctum token.
     */
    public function googleAuth(Request $request)
    {
        $request->validate(['id_token' => 'required|string']);

        $idToken = $request->id_token;

        // Verify the Google ID token via Google's HTTP endpoint (no extra packages needed)
        // https://developers.google.com/identity/sign-in/web/backend-auth
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (!$response->successful()) {
            return $this->error('Invalid Google ID token', 401);
        }

        $payload = $response->json();

        // Validate the audience (must match our Google Client ID)
        $expectedAudience = config('services.google.client_id');
        if ($payload['aud'] !== $expectedAudience) {
            return $this->error('Invalid Google token audience', 401);
        }

        $googleId = $payload['sub'];
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? $payload['email'] ?? 'Google User';

        if (!$email) {
            return $this->error('Google account does not have an email address', 400);
        }

        // Also verify the email is verified by Google
        if (($payload['email_verified'] ?? 'false') !== 'true') {
            return $this->error('Google email is not verified', 400);
        }

        // Find existing user by google_id or email
        $user = User::where('google_id', $googleId)->orWhere('email', $email)->first();

        if ($user) {
            // Existing user — link google_id if not already set
            if (!$user->google_id) {
                $user->update(['google_id' => $googleId]);
            }
        } else {
            // New user — register with Google
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'password' => Hash::make(Str::random(32)),
            ]);

            $user->assignRole('user');

            // Grant free signup credits
            $this->creditService->grantSignupCredits($user);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => $this->formatUser($user),
            'token' => $token,
        ], $user->wasRecentlyCreated ? 'Account created successfully' : 'Login successful');
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
            'referral_code' => $user->referral_code,
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
