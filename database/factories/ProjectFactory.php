<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name).'-'.fake()->randomNumber(4),
            'logo' => fake()->imageUrl(100, 100, 'business'),
            'plan' => 'free',
            'billing_cycle_start' => 1, // First day of the month
            'usage' => 0,
            'usage_limit' => 1000,
            'links_usage' => 0,
            'links_limit' => 10,
            'domains_limit' => 1,
            'tags_limit' => 5,
            'users_limit' => 1,
            'ai_usage' => 0,
            'ai_limit' => 10,
            'webhook_enabled' => false,
            'monthly_clicks' => 0,
            'current_month' => now()->format('Y-m'),
            'active_links' => 0,
        ];
    }
}
