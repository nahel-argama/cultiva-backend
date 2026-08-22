<?php

namespace Database\Factories;

use Cultiva\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
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
        $password = $this->faker->password();

        return [
            'name'              => $this->faker->name(),
            'email'             => $this->faker->email(),
            'password'          => Hash::make($password),
            'email_verified_at' => now(),
            'remember_token'    => null,
            'last_login'        => null,
            'is_retailer'       => false,
            'is_producer'       => false,
            'is_active'         => true,
        ];
    }
}
