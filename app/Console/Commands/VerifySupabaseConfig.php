<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerifySupabaseConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supabase:verify-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify Supabase authentication configuration and connectivity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verifying Supabase Configuration...');
        $this->newLine();

        $supabaseAuth = app(\App\Services\SupabaseAuthService::class);
        $verification = $supabaseAuth->verifyConfiguration();

        if ($verification['configured']) {
            $this->info('✅ Supabase configuration is valid!');
            $this->newLine();

            $this->info('📋 Configuration Details:');
            $this->table(
                ['Setting', 'Value'],
                [
                    ['Project URL', $verification['config']['url']],
                    ['Project Reference', $verification['config']['project_ref']],
                    ['JWKS URL', $verification['config']['jwks_url']],
                    ['JWT Issuer', $verification['config']['issuer']],
                    ['JWT Audience', $verification['config']['audience']],
                ]
            );

            return 0;
        } else {
            $this->error('❌ Supabase configuration has issues:');
            $this->newLine();

            foreach ($verification['issues'] as $issue) {
                $this->error("  • {$issue}");
            }

            $this->newLine();
            $this->info('💡 To fix these issues:');
            $this->info('1. Copy .env.supabase.example to .env');
            $this->info('2. Update the Supabase credentials in your .env file');
            $this->info('3. Run this command again to verify');

            return 1;
        }
    }
}
