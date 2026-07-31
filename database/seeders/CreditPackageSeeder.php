<?php

namespace Database\Seeders;

use App\Models\CreditPackage;
use Illuminate\Database\Seeder;

class CreditPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => 'Single Test', 'credits' => 1, 'price_kobo' => 20000, 'currency' => 'NGN', 'sort_order' => 1],
            ['name' => 'Quick Check (3)', 'credits' => 3, 'price_kobo' => 50000, 'currency' => 'NGN', 'sort_order' => 2],
            ['name' => 'Value Pack (10)', 'credits' => 10, 'price_kobo' => 150000, 'currency' => 'NGN', 'sort_order' => 3],
            ['name' => 'Family Pack (25)', 'credits' => 25, 'price_kobo' => 300000, 'currency' => 'NGN', 'sort_order' => 4],
            ['name' => 'Health Partner (50)', 'credits' => 50, 'price_kobo' => 500000, 'currency' => 'NGN', 'sort_order' => 5],
        ];

        foreach ($packages as $pkg) {
            CreditPackage::firstOrCreate(
                ['name' => $pkg['name']],
                $pkg,
            );
        }
    }
}
