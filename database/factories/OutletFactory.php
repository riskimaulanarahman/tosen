<?php

namespace Database\Factories;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Outlet>
 */
class OutletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory()->owner(),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'latitude' => fake()->latitude(-8.5, -8.7), // Bali area
            'longitude' => fake()->longitude(115.1, 115.3), // Bali area
            'radius' => fake()->numberBetween(50, 200), // 50-200 meters
            'is_active' => true,
            'description' => fake()->optional(0.7)->sentence(),
        ];
    }

    /**
     * Indicate that the outlet is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create outlet for a specific owner.
     */
    public function forOwner($ownerId): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $ownerId,
        ]);
    }
}
