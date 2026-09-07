<?php

namespace Database\Factories;

use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

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
            'email'             => $this->faker->unique()->safeEmail(),
            'password'          => Hash::make($password),
            'email_verified_at' => now(),
            'remember_token'    => null,
            'last_login'        => null,
            'is_active'         => true,
            'profile_type'      => ProfileType::PRODUCER,
        ];
    }

    public function retailer(): static
    {
        return $this->state(fn() => [
            'profile_type' => ProfileType::RETAILER,
        ]);
    }

    public function delivery(): static
    {
        return $this->state(fn() => [
            'profile_type' => ProfileType::DELIVERY,
        ]);
    }
}
