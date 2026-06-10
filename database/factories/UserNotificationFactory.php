<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserNotificationFactory extends Factory
{
    protected $model = UserNotification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['system', 'new_client', 'warning', 'info']),
            'title' => $this->faker->sentence(6),
            'message' => $this->faker->paragraph(),
            'data' => [
                'url' => $this->faker->url(),
                'timestamp' => now()->toISOString()
            ],
            'action_url' => $this->faker->url(),
            'icon' => $this->faker->randomElement([
                'fas fa-bell',
                'fas fa-user-plus',
                'fas fa-exclamation-triangle',
                'fas fa-info-circle',
                'fas fa-check-circle'
            ]),
            'read_at' => $this->faker->boolean(30) ? now() : null,
        ];
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    public function newClient(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'new_client',
            'title' => 'Nuevo Cliente Registrado',
            'message' => 'Se ha registrado un nuevo cliente en el sistema',
            'icon' => 'fas fa-user-plus',
            'action_url' => '/clientes',
        ]);
    }
}
