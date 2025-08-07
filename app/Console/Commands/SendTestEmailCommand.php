<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendTestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email : Email address to send test email to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify email configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address provided.');

            return 1;
        }

        $this->info('📧 Sending test email...');
        $this->info("To: {$email}");
        $this->newLine();

        $emailService = new \App\Services\EmailService;

        // Show email configuration
        $stats = $emailService->getEmailStats();
        $this->info('📋 Email Configuration:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Service', $stats['service']],
                ['Configured', $stats['configured'] ? 'Yes' : 'No'],
                ['From Address', $stats['from_address']],
                ['From Name', $stats['from_name']],
                ['Mailer', $stats['mailer']],
                ['Queue Enabled', $stats['queue_enabled'] ? 'Yes' : 'No'],
            ]
        );
        $this->newLine();

        if (! $stats['configured']) {
            $this->error('❌ Email service is not properly configured.');
            $this->info('Please check your RESEND_API_KEY in the .env file.');

            return 1;
        }

        // Send test email
        $this->info('Sending test email...');

        if ($emailService->sendTestEmail($email)) {
            $this->info('✅ Test email sent successfully!');
            $this->info("Check the inbox for {$email}");

            return 0;
        } else {
            $this->error('❌ Failed to send test email.');
            $this->info('Check the logs for more details.');

            return 1;
        }
    }
}
