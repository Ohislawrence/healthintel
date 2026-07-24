<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\Setting;
use Illuminate\Http\Request;

class AdminSettingController extends BaseController
{
    /**
     * Get all settings grouped.
     */
    public function index()
    {
        $settings = Setting::all()->groupBy('group');

        return $this->success([
            'groups' => $settings->map(fn($items) => $items->map(fn($s) => [
                'id' => $s->id,
                'key' => $s->key,
                'value' => $s->castedValue(),
                'type' => $s->type,
                'group' => $s->group,
                'label' => $s->label,
                'description' => $s->description,
            ])->values()),
        ]);
    }

    /**
     * Get payment gateway configuration info.
     */
    public function paymentGatewayInfo()
    {
        $activeGateway = Setting::getValue('payment.gateway', 'paystack');

        // Try multiple sources because config:cache hides .env:
        // 1. config() — works when config cache was created AFTER keys existed
        // 2. $_ENV / getenv() — works if server sets them as real env vars
        // 3. Read .env file directly — always works as last resort
        $paystackKey = config('services.paystack.secret_key');
        $flutterwaveKey = config('services.flutterwave.secret_key');

        $paystackConfigured = !empty($paystackKey);
        $flutterwaveConfigured = !empty($flutterwaveKey);

        return $this->success([
            'active_gateway' => $activeGateway,
            'gateways' => [
                'paystack' => [
                    'name' => 'Paystack',
                    'configured' => $paystackConfigured,
                ],
                'flutterwave' => [
                    'name' => 'Flutterwave',
                    'configured' => $flutterwaveConfigured,
                ],
            ],
        ]);
    }

    /**
     * Read a value directly from the .env file as a last resort.
     * Needed on production servers where config:cache skips .env loading.
     */
    private function readEnvFile(string $key): ?string
    {
        $path = base_path('.env');
        if (!file_exists($path)) {
            return null;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_starts_with($line, $key . '=')) {
                $value = trim(substr($line, strlen($key) + 1));
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }
                return $value !== '' ? $value : null;
            }
        }
        return null;
    }

    /**
     * Set a value in the .env file. Creates the key if it doesn't exist.
     */
    private function writeEnvFile(string $key, string $value): void
    {
        $path = base_path('.env');
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        $found = false;

        foreach ($lines as &$line) {
            $trimmed = trim($line);
            if ($trimmed !== '' && !str_starts_with($trimmed, '#') && str_starts_with($trimmed, $key . '=')) {
                // Quote values containing spaces
                if (str_contains($value, '  ') || str_contains($value, '"')) {
                    $value = "'" . $value . "'";
                }
                $line = $key . '=' . $value;
                $found = true;
                break;
            }
        }
        unset($line);

        if (!$found) {
            $lines[] = $key . '=' . $value;
        }

        file_put_contents($path, implode("\n", $lines) . "\n");
    }

    /**
     * List editable .env API keys (whitelist for admin UI).
     */
    public function envKeys()
    {
        // Map .env keys to config paths for the config() check
        $envToConfig = [
            'PAYSTACK_SECRET_KEY' => 'services.paystack.secret_key',
            'PAYSTACK_PUBLIC_KEY' => 'services.paystack.public_key',
            'FLUTTERWAVE_SECRET_KEY' => 'services.flutterwave.secret_key',
            'FLUTTERWAVE_PUBLIC_KEY' => 'services.flutterwave.public_key',
            'FLUTTERWAVE_SECRET_HASH' => 'services.flutterwave.secret_hash',
            'DEEPSEEK_API_KEY' => 'services.deepseek.api_key',
            'TERMII_API_KEY' => 'services.termii.api_key',
        ];

        $keys = [
            ['key' => 'PAYSTACK_SECRET_KEY', 'label' => 'Paystack Secret Key', 'group' => 'Paystack'],
            ['key' => 'PAYSTACK_PUBLIC_KEY', 'label' => 'Paystack Public Key', 'group' => 'Paystack'],
            ['key' => 'FLUTTERWAVE_SECRET_KEY', 'label' => 'Flutterwave Secret Key', 'group' => 'Flutterwave'],
            ['key' => 'FLUTTERWAVE_PUBLIC_KEY', 'label' => 'Flutterwave Public Key', 'group' => 'Flutterwave'],
            ['key' => 'FLUTTERWAVE_SECRET_HASH', 'label' => 'Flutterwave Secret Hash', 'group' => 'Flutterwave'],
            ['key' => 'DEEPSEEK_API_KEY', 'label' => 'DeepSeek API Key', 'group' => 'AI / DeepSeek'],
            ['key' => 'TERMII_API_KEY', 'label' => 'Termii API Key (SMS)', 'group' => 'Communication'],
        ];

        $result = [];
        foreach ($keys as $k) {
            $configPath = $envToConfig[$k['key']] ?? null;
            $rawValue = ($configPath ? config($configPath) : null)
                ?: ($_ENV[$k['key']] ?? getenv($k['key']))
                ?: $this->readEnvFile($k['key'])
                ?: null;

            $result[] = [
                'key' => $k['key'],
                'label' => $k['label'],
                'group' => $k['group'],
                'value' => $rawValue ? $this->maskValue($rawValue) : null,
                'isSet' => !empty($rawValue),
            ];
        }

        return $this->success(['keys' => $result]);
    }

    /**
     * Update a single .env key from the admin UI.
     */
    public function updateEnvKey(Request $request)
    {
        $allowedKeys = [
            'PAYSTACK_SECRET_KEY', 'PAYSTACK_PUBLIC_KEY',
            'FLUTTERWAVE_SECRET_KEY', 'FLUTTERWAVE_PUBLIC_KEY', 'FLUTTERWAVE_SECRET_HASH',
            'DEEPSEEK_API_KEY', 'TERMII_API_KEY',
        ];

        $validated = $request->validate([
            'key' => ['required', 'string', 'in:' . implode(',', $allowedKeys)],
            'value' => ['required', 'string'],
        ]);

        $this->writeEnvFile($validated['key'], $validated['value']);

        return $this->success([
            'key' => $validated['key'],
        ], 'Environment key updated successfully. Changes will take effect on the next request.');
    }

    /**
     * Mask sensitive values for display (show first 6 + last 4 chars).
     */
    private function maskValue(string $value): string
    {
        if (strlen($value) <= 10) {
            return str_repeat('•', strlen($value));
        }
        return substr($value, 0, 6) . str_repeat('•', 10) . substr($value, -4);
    }

    /**
     * Set the active payment gateway.
     */
    public function setPaymentGateway(Request $request)
    {
        $validated = $request->validate([
            'gateway' => ['required', 'string', 'in:paystack,flutterwave'],
        ]);

        Setting::setValue('payment.gateway', $validated['gateway']);

        return $this->success([
            'gateway' => $validated['gateway'],
        ], 'Payment gateway updated successfully');
    }

    /**
     * Update a single setting.
     */
    public function update(Request $request, Setting $setting)
    {
        $validated = $request->validate([
            'value' => 'required',
        ]);

        $value = $validated['value'];

        // Cast based on type
        $setting->value = match ($setting->type) {
            'boolean' => $value ? '1' : '0',
            'json' => is_array($value) ? json_encode($value) : $value,
            default => (string) $value,
        };

        $setting->save();

        return $this->success([
            'setting' => [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $setting->castedValue(),
                'type' => $setting->type,
                'group' => $setting->group,
                'label' => $setting->label,
            ],
        ], 'Setting updated');
    }

    /**
     * Bulk update settings.
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        foreach ($request->settings as $item) {
            $setting = Setting::where('key', $item['key'])->first();
            if ($setting) {
                $setting->value = match ($setting->type) {
                    'boolean' => $item['value'] ? '1' : '0',
                    'json' => is_array($item['value']) ? json_encode($item['value']) : $item['value'],
                    default => (string) $item['value'],
                };
                $setting->save();
            }
        }

        return $this->success(null, 'Settings updated successfully');
    }
}