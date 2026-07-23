<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\CreditService;
use Illuminate\Database\Seeder;

class GrantCreditsSeeder extends Seeder
{
    public function run(): void
    {
        $cs = app(CreditService::class);
        foreach (User::all() as $user) {
            $cs->grantSignupCredits($user);
            echo '[Credits] ' . $user->email . ' balance: ' . $cs->getBalance($user) . PHP_EOL;
        }
    }
}
