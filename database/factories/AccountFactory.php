<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Model\Account;
use Hyperf\Database\Model\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected string $model = Account::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid,
            'name' => $this->faker->name,
            'balance' => $this->faker->randomFloat(2, 0, 10000),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the account has a specific balance.
     */
    public function withBalance(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $balance,
        ]);
    }

    /**
     * Indicate that the account has a high balance.
     */
    public function wealthy(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $this->faker->randomFloat(2, 5000, 50000),
        ]);
    }

    /**
     * Indicate that the account has a low balance.
     */
    public function lowBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $this->faker->randomFloat(2, 0, 100),
        ]);
    }

    /**
     * Indicate that the account has zero balance.
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => 0.00,
        ]);
    }
}