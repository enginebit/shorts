<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('project_id');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');

            // Stripe fields
            $table->string('stripe_invoice_id')->unique()->index();
            $table->string('stripe_customer_id')->index();
            $table->string('stripe_subscription_id')->nullable()->index();

            // Invoice details
            $table->string('number')->nullable();
            $table->string('status'); // draft, open, paid, void, uncollectible
            $table->integer('amount_due'); // in cents
            $table->integer('amount_paid')->default(0); // in cents
            $table->integer('amount_remaining')->default(0); // in cents
            $table->string('currency', 3)->default('USD');

            // Dates
            $table->timestamp('invoice_date');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();

            // Billing period
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();

            // Additional fields
            $table->text('description')->nullable();
            $table->json('line_items')->nullable();
            $table->string('hosted_invoice_url')->nullable();
            $table->string('invoice_pdf')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'invoice_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
