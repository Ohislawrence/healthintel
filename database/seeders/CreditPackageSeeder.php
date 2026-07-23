<?php

namespace Database\Seeders;

use App\Models\CreditPackage;
use Illuminate\Database\Seeder;

class CreditPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => '5 Credits', 'credits' => 5, 'price_kobo' => 50000, 'currency' => 'NGN', 'sort_order' => 1],
            ['name' => '10 Credits', 'credits' => 10, 'price_kobo' => 90000, 'currency' => 'NGN', 'sort_order' => 2],
            ['name' => '20 Credits', 'credits' => 20, 'price_kobo' => 160000, 'currency' => 'NGN', 'sort_order' => 3],
            ['name' => '50 Credits', 'credits' => 50, 'price_kobo' => 350000, 'currency' => 'NGN', 'sort_order' => 4],
        ];

        foreach ($packages as $pkg) {
            CreditPackage::firstOrCreate(
                ['name' => $pkg['name']],
                $pkg,
            );
        }
    }
}
