<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        // Get the current year
        $currentYear = date('Y');

        // Generate the remaining 6 digits for the ID
        $remainingDigits = $this->faker->numberBetween(100000,999999);

        // Combine them to form the ID
        $id = $currentYear . $remainingDigits;

        return [
            'id' => $id, 
            'first_name' => $this->faker->firstName,
            'middle_name' => $this->faker->optional()->lastName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'gender' => $this->faker->randomElement(['Male', 'Female']),
            'phone_number' => $this->faker->phoneNumber,
            'street' => $this->faker->streetAddress,
            'barangay' => $this->faker->word,
            'city_municipality' => $this->faker->city,
            'province' => $this->faker->state,
            'postal_code' => $this->faker->postcode,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
