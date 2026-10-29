<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\TaskColumn;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaskColumn> */
class TaskColumnFactory extends Factory
{
    protected $model = TaskColumn::class;

    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'title' => fake()->word(),
            'position' => 0,
        ];
    }
}
