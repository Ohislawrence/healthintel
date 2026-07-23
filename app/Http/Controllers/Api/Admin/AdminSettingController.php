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