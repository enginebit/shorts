<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestWelcomeEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-welcome {email : Email address to send welcome email to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test welcome email to verify email templates';

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

        $this->info('📧 Sending test welcome email...');
        $this->info("To: {$email}");
        $this->newLine();

        // Create a test user
        $testUser = new \App\Models\User([
            'id' => 'test_user_'.\Illuminate\Support\Str::random(10),
            'name' => 'Test User',
            'email' => $email,
            'email_verified_at' => now(),
        ]);

        // Create a test workspace
        $testWorkspace = new \App\Models\Workspace([
            'id' => 'test_workspace_'.\Illuminate\Support\Str::random(10),
            'name' => 'My First Workspace',
            'slug' => 'my-first-workspace',
        ]);

        try {
            // Send welcome email with test data
            \Illuminate\Support\Facades\Mail::to($email)
                ->send(new \App\Mail\WelcomeEmail($testUser, $testWorkspace));

            $this->info('✅ Test welcome email sent successfully!');
            $this->info("Check the inbox for {$email}");
            $this->newLine();
            $this->info('💡 This email includes:');
            $this->info('   - Welcome message with user name');
            $this->info('   - Default workspace information');
            $this->info('   - Getting started guide');
            $this->info('   - Helpful resources and links');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Failed to send test welcome email.');
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }
}
