<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class BookingSettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'bookings.credit_cost'],
            [
                'value' => '1',
                'type' => 'integer',
                'group' => 'credits',
                'label' => 'Provider Booking Credit Cost',
                'description' => 'Credits charged when a patient books an appointment with a provider (0 = free). Refunded if the provider declines.',
            ]
        );
    }
}