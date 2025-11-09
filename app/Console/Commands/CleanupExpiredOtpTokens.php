<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupExpiredOtpTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-expired-otp-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired OTP tokens from email_verifications table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of expired OTP tokens...');

        try {
            // Delete expired OTP tokens
            $deletedCount = DB::table('email_verifications')
                ->where('expires_at', '<', now())
                ->delete();

            if ($deletedCount > 0) {
                $this->info("Successfully deleted {$deletedCount} expired OTP tokens.");
                Log::info("Expired OTP cleanup: {$deletedCount} tokens deleted");
            } else {
                $this->info('No expired OTP tokens found.');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to cleanup expired OTP tokens: ' . $e->getMessage());
            Log::error('OTP cleanup failed: ' . $e->getMessage());
            return 1;
        }
    }
}
