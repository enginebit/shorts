<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EmailStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display email service configuration and statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📧 Email Service Statistics');
        $this->info('==========================');
        $this->newLine();

        $emailService = new \App\Services\EmailService;
        $stats = $emailService->getEmailStats();

        // Configuration Status
        $this->info('🔧 Configuration:');
        $this->table(
            ['Setting', 'Value', 'Status'],
            [
                ['Email Service', $stats['service'], $stats['configured'] ? '✅' : '❌'],
                ['From Address', $stats['from_address'], ! empty($stats['from_address']) ? '✅' : '❌'],
                ['From Name', $stats['from_name'], ! empty($stats['from_name']) ? '✅' : '✅'],
                ['Mailer Driver', $stats['mailer'], $stats['mailer'] === 'resend' ? '✅' : '⚠️'],
                ['Queue Enabled', $stats['queue_enabled'] ? 'Yes' : 'No', $stats['queue_enabled'] ? '✅' : '⚠️'],
            ]
        );
        $this->newLine();

        // Environment Variables
        $this->info('🌍 Environment Variables:');
        $this->table(
            ['Variable', 'Set', 'Value'],
            [
                ['RESEND_API_KEY', ! empty(config('services.resend.key')) ? 'Yes' : 'No',
                    ! empty(config('services.resend.key')) ? substr(config('services.resend.key'), 0, 8).'...' : 'Not set'],
                ['MAIL_MAILER', ! empty(config('mail.default')) ? 'Yes' : 'No', config('mail.default', 'Not set')],
                ['MAIL_FROM_ADDRESS', ! empty(config('mail.from.address')) ? 'Yes' : 'No', config('mail.from.address', 'Not set')],
                ['MAIL_FROM_NAME', ! empty(config('mail.from.name')) ? 'Yes' : 'No', config('mail.from.name', 'Not set')],
                ['QUEUE_CONNECTION', ! empty(config('queue.default')) ? 'Yes' : 'No', config('queue.default', 'Not set')],
            ]
        );
        $this->newLine();

        // Queue Information
        $this->info('📋 Queue Information:');
        $queueConnection = config('queue.default');
        $queueConfig = config("queue.connections.{$queueConnection}");

        $this->table(
            ['Setting', 'Value'],
            [
                ['Default Connection', $queueConnection],
                ['Driver', $queueConfig['driver'] ?? 'Unknown'],
                ['Queue Name', $queueConfig['queue'] ?? 'default'],
            ]
        );
        $this->newLine();

        // Health Check
        if ($stats['configured']) {
            $this->info('✅ Email service is properly configured and ready to use.');
            $this->info('💡 Run "php artisan email:test your@email.com" to send a test email.');
        } else {
            $this->error('❌ Email service is not properly configured.');
            $this->info('🔧 Please check the following:');
            $this->info('   1. Set RESEND_API_KEY in your .env file');
            $this->info('   2. Set MAIL_MAILER=resend in your .env file');
            $this->info('   3. Configure MAIL_FROM_ADDRESS and MAIL_FROM_NAME');
        }

        $this->newLine();
        $this->info('📚 Documentation: https://resend.com/docs/send-with-laravel');

        return 0;
    }
}
