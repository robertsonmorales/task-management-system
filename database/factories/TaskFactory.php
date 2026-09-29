<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_name' => fake()->sentence(),
            'due_date' => fake()->dateTimeBetween('now', '+1 month'),
            'task_description' => fake()->paragraph(),
            'priority' => fake()->randomElement(['Low', 'Normal', 'High', 'Urgent']),
            'status' => fake()->randomElement(['Pending', 'In Progress', 'Completed']),
            'user_id' => User::factory(),
        ];
    }
}
