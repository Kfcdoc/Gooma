<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_GESTOR,
            'client_id' => null,
            'active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
            'client_id' => null,
        ]);
    }

    public function gestor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_GESTOR,
            'client_id' => null,
        ]);
    }

    public function cliente(Client|string|null $client = null): static
    {
        return $this->state(function () use ($client) {
            $clientId = $client instanceof Client ? $client->getKey() : $client;

            return [
                'role' => User::ROLE_CLIENTE,
                'client_id' => $clientId ?? Client::factory()->create()->getKey(),
            ];
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
