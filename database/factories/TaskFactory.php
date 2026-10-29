<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskColumn;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'task_column_id' => TaskColumn::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'position' => 0,
        ];
    }
}
