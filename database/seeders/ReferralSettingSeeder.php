<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class ReferralSettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'referral.percentage'],
            [
                'value' => '10',
                'type' => 'integer',
                'group' => 'referral',
                'label' => 'Referral Commission Percentage',
                'description' => 'Percentage of the referred user\'s payment that the referrer earns (e.g., 10 means 10%)',
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'referral.max_payouts_per_referral'],
            [
                'value' => '3',
                'type' => 'integer',
                'group' => 'referral',
                'label' => 'Max Payouts Per Referral',
                'description' => 'Maximum number of purchases the referrer gets paid for per referred user',
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'referral.min_payout_threshold_naira'],
            [
                'value' => '5000',
                'type' => 'integer',
                'group' => 'referral',
                'label' => 'Minimum Payout Threshold (₦)',
                'description' => 'Minimum earnings in Naira before a user can request a payout',
            ]
        );
    }
}