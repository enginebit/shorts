<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Link>
 */
class LinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => ProjectFactory::new(),
            'domain' => $this->faker->domainName(),
            'key' => $this->faker->slug(2),
            'url' => $this->faker->url(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(10),
            'image' => $this->faker->imageUrl(),
            'clicks' => $this->faker->numberBetween(0, 1000),
            'unique_clicks' => $this->faker->numberBetween(0, 500),
            'last_clicked' => $this->faker->optional()->dateTimeBetween('-30 days'),
            'expires_at' => $this->faker->optional()->dateTimeBetween('+1 day', '+1 year'),
            'password' => $this->faker->optional()->password(),
            'ios_targeting' => $this->faker->optional()->url(),
            'android_targeting' => $this->faker->optional()->url(),
            'geo_targeting' => $this->faker->optional()->randomElement([
                json_encode(['US', 'CA']),
                json_encode(['GB', 'DE', 'FR']),
                null,
            ]),
            'utm_source' => $this->faker->optional()->word(),
            'utm_medium' => $this->faker->optional()->word(),
            'utm_campaign' => $this->faker->optional()->word(),
            'utm_term' => $this->faker->optional()->word(),
            'utm_content' => $this->faker->optional()->word(),
        ];
    }
}
