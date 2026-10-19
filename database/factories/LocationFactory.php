<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = fake()->unique()->city();

        return [
            'name' => $city,
            'address' => fake()->streetAddress(),
            'city' => $city,
            'state' => 'TX',
            'is_active' => true,
            'name_to_be_confirmed' => false,
        ];
    }

    /**
     * Indicate that the facility is hidden from new inspections.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the client still has to confirm the facility's name.
     */
    public function nameToBeConfirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'name_to_be_confirmed' => true,
        ]);
    }
}
