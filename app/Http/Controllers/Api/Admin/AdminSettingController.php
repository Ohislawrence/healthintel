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
        $paystackKey = config('services.paystack.secret_key')
            ?: ($_ENV['PAYSTACK_SECRET_KEY'] ?? getenv('PAYSTACK_SECRET_KEY'))
            ?: $this->readEnvFile('PAYSTACK_SECRET_KEY')
            ?: null;
        $flutterwaveKey = config('services.flutterwave.secret_key')
            ?: ($_ENV['FLUTTERWAVE_SECRET_KEY'] ?? getenv('FLUTTERWAVE_SECRET_KEY'))
            ?: $this->readEnvFile('FLUTTERWAVE_SECRET_KEY')
            ?: null;

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