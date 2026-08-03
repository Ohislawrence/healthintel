<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ReferralProgramService;
use Illuminate\Console\Command;

class GenerateMissingReferralCodes extends Command
{
    protected $signature = 'referral:generate-missing-codes';
    protected $description = 'Generate referral codes for all existing users who do not have one';

    public function handle(ReferralProgramService $service): int
    {
        $users = User::whereNull('referral_code')->get();

        if ($users->isEmpty()) {
            $this->info('All users already have referral codes.');
            return self::SUCCESS;
        }

        $count = $users->count();
        $this->info("Found {$count} users without referral codes. Generating...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $generated = 0;
        foreach ($users as $user) {
            $service->generateReferralCode($user);
            $generated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Successfully generated referral codes for {$generated} users.");

        return self::SUCCESS;
    }
}