<?php

namespace Database\Factories;

use App\Models\PsgcVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class PsgcVersionFactory extends Factory
{
    protected $model = PsgcVersion::class;

    public function definition(): array
    {
        return [
            'quarter' => fake()->randomElement(['1Q', '2Q', '3Q', '4Q']),
            'year' => fake()->numberBetween(2020, 2026),
            'publication_date' => fake()->dateBetween('-2 years', 'now'),
            'download_url' => 'https://psa.gov.ph/file.xlsx',
            'filename' => 'PSGC-4Q-2025-Publication-Datafile.xlsx',
            'is_current' => fake()->boolean(20), // 20% chance of being current
            'regions_count' => fake()->numberBetween(10, 20),
            'provinces_count' => fake()->numberBetween(70, 90),
            'cities_municipalities_count' => fake()->numberBetween(150, 170),
            'barangays_count' => fake()->numberBetween(40000, 45000),
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => ['is_current' => true]);
    }

    public function historical(): static
    {
        return $this->state(fn (array $attributes) => ['is_current' => false]);
    }
}
