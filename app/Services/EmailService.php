<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * Available filters for targeting users.
     */
    public const FILTER_ROLES = 'roles';
    public const FILTER_HAS_SUBMISSIONS = 'has_submissions';
    public const FILTER_MIN_CREDITS = 'min_credits';
    public const FILTER_SIGNUP_FROM = 'signup_from';
    public const FILTER_SIGNUP_TO = 'signup_to';
    public const FILTER_EMAIL_VERIFIED = 'email_verified';
    public const FILTER_USER_IDS = 'user_ids';

    /**
     * Build a query for users matching the given filters.
     *
     * @param array $filters  e.g. ['roles' => ['user'], 'has_submissions' => true, 'signup_from' => '2025-01-01']
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function queryUsersByFilters(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = User::query();

        // Filter by roles
        if (!empty($filters[self::FILTER_ROLES])) {
            $roles = (array) $filters[self::FILTER_ROLES];
            $query->whereHas('roles', function ($q) use ($roles) {
                $q->whereIn('name', $roles);
            });
        }

        // Filter by whether user has any lab submissions
        if (array_key_exists(self::FILTER_HAS_SUBMISSIONS, $filters) && $filters[self::FILTER_HAS_SUBMISSIONS] !== null) {
            if ($filters[self::FILTER_HAS_SUBMISSIONS]) {
                $query->has('labSubmissions');
            } else {
                $query->doesntHave('labSubmissions');
            }
        }

        // Filter by email verification
        if (array_key_exists(self::FILTER_EMAIL_VERIFIED, $filters) && $filters[self::FILTER_EMAIL_VERIFIED] !== null) {
            if ($filters[self::FILTER_EMAIL_VERIFIED]) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Filter by signup date range
        if (!empty($filters[self::FILTER_SIGNUP_FROM])) {
            $query->where('created_at', '>=', $filters[self::FILTER_SIGNUP_FROM]);
        }
        if (!empty($filters[self::FILTER_SIGNUP_TO])) {
            $query->where('created_at', '<=', $filters[self::FILTER_SIGNUP_TO] . ' 23:59:59');
        }

        // Filter by specific user IDs
        if (!empty($filters[self::FILTER_USER_IDS])) {
            $ids = (array) $filters[self::FILTER_USER_IDS];
            $query->whereIn('id', $ids);
        }

        return $query;
    }

    /**
     * Replace personalisation tokens in the template string.
     *
     * Supported tokens:
     *   {{name}}         - User's name
     *   {{email}}        - User's email
     *   {{phone}}        - User's phone (or 'N/A')
     *   {{credits}}      - Credit balance
     *   {{signup_date}}  - Account creation date
     *   {{dashboard_url}} - Link to dashboard
     *
     * @param string $template
     * @param User   $user
     * @return string
     */
    public function personalise(string $template, User $user): string
    {
        $creditService = app(CreditService::class);
        $dashboardUrl = config('app.url') . '/dashboard';

        $replacements = [
            '{{name}}'          => e($user->name),
            '{{email}}'         => e($user->email),
            '{{phone}}'         => e($user->phone ?: 'N/A'),
            '{{credits}}'       => (string) $creditService->getBalance($user),
            '{{signup_date}}'   => $user->created_at ? $user->created_at->format('F j, Y') : '',
            '{{dashboard_url}}' => $dashboardUrl,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Send a single personalised email.
     *
     * @param User   $user
     * @param string $subject    (with optional {{tokens}})
     * @param string $bodyHtml   (HTML with {{tokens}})
     * @param string $bodyText   (plain text with {{tokens}}, optional)
     * @return bool
     */
    public function sendToUser(User $user, string $subject, string $bodyHtml, ?string $bodyText = null): bool
    {
        $subject = $this->personalise($subject, $user);
        $html    = $this->personalise($bodyHtml, $user);
        $text    = $bodyText ? $this->personalise($bodyText, $user) : null;

        try {
            Mail::send([], [], function ($message) use ($user, $subject, $html, $text) {
                $message->to($user->email, $user->name)
                    ->subject($subject)
                    ->html($html);

                if ($text) {
                    $message->text($text);
                }
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('EmailService: Failed to send email to ' . $user->email . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send emails in bulk to users matching the given filters.
     *
     * @param array  $filters
     * @param string $subject
     * @param string $bodyHtml
     * @param string|null $bodyText
     * @return array{total: int, sent: int, failed: int}
     */
    public function sendBulk(array $filters, string $subject, string $bodyHtml, ?string $bodyText = null): array
    {
        $query = $this->queryUsersByFilters($filters);

        // Eager load roles for personalisation
        $query->with('roles');

        $total = 0;
        $sent = 0;
        $failed = 0;

        $query->chunk(100, function ($users) use ($subject, $bodyHtml, $bodyText, &$total, &$sent, &$failed) {
            /** @var User $user */
            foreach ($users as $user) {
                $total++;
                if ($this->sendToUser($user, $subject, $bodyHtml, $bodyText)) {
                    $sent++;
                } else {
                    $failed++;
                }
            }
        });

        return [
            'total'  => $total,
            'sent'   => $sent,
            'failed' => $failed,
        ];
    }

    /**
     * Count users matching the given filters (for preview before sending).
     *
     * @param array $filters
     * @return int
     */
    public function countRecipients(array $filters): int
    {
        return $this->queryUsersByFilters($filters)->count();
    }

    /**
     * Send the welcome email for a newly verified user.
     */
    public function sendWelcomeEmail(User $user): void
    {
        $featuresList = [
            '🔬 Upload lab reports (PDF) and get plain-language explanations',
            '📊 Enter lab values manually and see them checked against real reference ranges',
            '🩺 Use the Symptom Checker to suggest relevant tests',
            '🏥 Find hospitals, labs, and specialists near you in the Provider Directory',
            '📅 Track appointments and set reminders',
            '📈 Monitor health metrics (BMI, blood pressure, glucose) over time',
            '🛡️ Compare health insurance plans',
        ];

        $creditService = app(CreditService::class);
        $credits = $creditService->getBalance($user);

        $html = $this->buildWelcomeHtml($user, $featuresList, $credits);

        try {
            Mail::send([], [], function ($message) use ($user, $html) {
                $message->to($user->email, $user->name)
                    ->subject('Welcome to HealthIntel, ' . $user->name . '! 🎉')
                    ->html($html);
            });
        } catch (\Throwable $e) {
            Log::warning('Failed to send welcome email to user ' . $user->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Build the HTML for the welcome email.
     */
    private function buildWelcomeHtml(User $user, array $featuresList, int $credits): string
    {
        $featuresHtml = implode('', array_map(fn($f) => '<li style="line-height:1.8;">' . e($f) . '</li>', $featuresList));
        $dashboardUrl = config('app.url') . '/dashboard';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="UTF-8"></head>
        <body style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;color:#1B2622;background:#F4F6F3;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border-radius:14px;overflow:hidden;border:1px solid #DCE3DE;">
                <tr>
                    <td style="padding:32px 28px 24px;background:linear-gradient(135deg,#0E6B5C,#0a5548);">
                        <h1 style="color:#FFFFFF;margin:0;font-size:22px;">Welcome to HealthIntel!</h1>
                        <p style="color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:14px;">Understand your health, in plain language.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <p style="font-size:16px;line-height:1.6;margin:0 0 8px;">Hi <strong>{$this->e($user->name)}</strong>,</p>
                        <p style="font-size:14px;line-height:1.7;color:#57645D;margin:0 0 20px;">
                            Your email has been verified and your account is ready. HealthIntel is your personal health companion — we turn complex lab results into clear, actionable insights so you always know where you stand.
                        </p>

                        <h2 style="font-size:16px;color:#0E6B5C;margin:0 0 12px;">What You Can Do:</h2>
                        <ul style="font-size:14px;color:#57645D;padding-left:20px;margin:0 0 24px;">
                            {$featuresHtml}
                        </ul>

                        <!-- Credits badge -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:rgba(14,107,92,0.06);border-radius:10px;margin-bottom:24px;">
                            <tr>
                                <td style="padding:16px 20px;text-align:center;">
                                    <p style="font-size:13px;color:#57645D;margin:0 0 4px;">You're starting with</p>
                                    <p style="font-size:28px;font-weight:700;color:#0E6B5C;margin:0;line-height:1;">{$credits}</p>
                                    <p style="font-size:13px;color:#57645D;margin:4px 0 0;">free credits</p>
                                </td>
                            </tr>
                        </table>

                        <p style="font-size:14px;color:#57645D;line-height:1.7;margin:0 0 8px;">
                            Each lab interpretation uses 1 credit. You can buy more credits at any time from within the app.
                        </p>

                        <h2 style="font-size:16px;color:#0E6B5C;margin:24px 0 12px;">Navigating Your Portal:</h2>
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:24px;">
                            <tr><td style="padding:10px 12px;border-bottom:1px solid #E8EBE7;font-size:14px;color:#1B2622;"><strong>📊 Lab Results</strong></td><td style="padding:10px 12px;border-bottom:1px solid #E8EBE7;font-size:13px;color:#57645D;">Upload PDFs or enter values from your lab report</td></tr>
                            <tr style="background:#F9FAFB;"><td style="padding:10px 12px;border-bottom:1px solid #E8EBE7;font-size:14px;color:#1B2622;"><strong>🩺 Symptom Checker</strong></td><td style="padding:10px 12px;border-bottom:1px solid #E8EBE7;font-size:13px;color:#57645D;">Describe symptoms and get suggested tests</td></tr>
                            <tr><td style="padding:10px 12px;border-bottom:1px solid #E8EBE7;font-size:14px;color:#1B2622;"><strong>🏥 Directory</strong></td><td style="padding:10px 12px;border-bottom:1px solid #E8EBE7;font-size:13px;color:#57645D;">Find nearby hospitals, labs & specialists</td></tr>
                            <tr style="background:#F9FAFB;"><td style="padding:10px 12px;border-bottom:1px solid #E8EBE7;font-size:14px;color:#1B2622;"><strong>🛠️ Health Tools</strong></td><td style="padding:10px 12px;border-bottom:1px solid #E8EBE7;font-size:13px;color:#57645D;">BMI, BMR, blood pressure log, period tracker & more</td></tr>
                            <tr><td style="padding:10px 12px;font-size:14px;color:#1B2622;"><strong>👤 Profile</strong></td><td style="padding:10px 12px;font-size:13px;color:#57645D;">Complete your health profile for personalised insights</td></tr>
                        </table>

                        <a href="{$dashboardUrl}" style="display:inline-block;background:#0E6B5C;color:#FFFFFF;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:600;font-size:15px;">Go to Your Dashboard →</a>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 28px;background:#F9FAFB;border-top:1px solid #E8EBE7;font-size:12px;color:#9CA3AF;line-height:1.6;">
                        HealthIntel — Understand your health, in plain language.<br>
                        This is an automated message sent because you created an account.
                    </td>
                </tr>
            </table>
        </body>
        </html>
        HTML;
    }

    /**
     * Available placeholder tokens documentation (for the admin UI hints).
     */
    public function availableTokens(): array
    {
        return [
            ['token' => '{{name}}',          'description' => "User's full name"],
            ['token' => '{{email}}',         'description' => "User's email address"],
            ['token' => '{{phone}}',         'description' => "User's phone number"],
            ['token' => '{{credits}}',       'description' => 'Current credit balance'],
            ['token' => '{{signup_date}}',   'description' => 'Account creation date'],
            ['token' => '{{dashboard_url}}', 'description' => 'Link to user dashboard'],
        ];
    }

    /**
     * Escape for HTML, with fallback if the e() helper is not available in this context.
     */
    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}