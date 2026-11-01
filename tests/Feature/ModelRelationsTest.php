<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Task;
use App\Models\TaskColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_board_columns_come_back_ordered_by_position(): void
    {
        $board = Board::create(['title' => 'B']);
        TaskColumn::create(['board_id' => $board->id, 'title' => 'last', 'position' => 2]);
        TaskColumn::create(['board_id' => $board->id, 'title' => 'first', 'position' => 0]);
        TaskColumn::create(['board_id' => $board->id, 'title' => 'middle', 'position' => 1]);

        $this->assertSame(['first', 'middle', 'last'], $board->columns->pluck('title')->all());
    }

    public function test_column_tasks_come_back_ordered_and_only_for_that_column(): void
    {
        $board = Board::create(['title' => 'B']);
        $a = TaskColumn::create(['board_id' => $board->id, 'title' => 'A', 'position' => 0]);
        $b = TaskColumn::create(['board_id' => $board->id, 'title' => 'B', 'position' => 1]);
        Task::create(['task_column_id' => $a->id, 'title' => 'two', 'position' => 1]);
        Task::create(['task_column_id' => $a->id, 'title' => 'one', 'position' => 0]);
        Task::create(['task_column_id' => $b->id, 'title' => 'elsewhere', 'position' => 0]);

        $this->assertSame(['one', 'two'], $a->tasks->pluck('title')->all());
    }

    public function test_belongs_to_relations_point_back_up(): void
    {
        $board = Board::create(['title' => 'B']);
        $col = TaskColumn::create(['board_id' => $board->id, 'title' => 'C', 'position' => 0]);
        $task = Task::create(['task_column_id' => $col->id, 'title' => 'T', 'position' => 0]);

        $this->assertTrue($task->column->is($col));
        $this->assertTrue($col->board->is($board));
    }

    public function test_mass_assignment_ignores_fields_that_are_not_fillable(): void
    {
        $board = Board::create(['title' => 'B']);
        $col = TaskColumn::create(['board_id' => $board->id, 'title' => 'C', 'position' => 0]);
        $task = new Task(['task_column_id' => $col->id, 'title' => 'T', 'position' => 0, 'id' => 999]);

        $this->assertNull($task->id);
    }

    public function test_factories_build_a_valid_chain(): void
    {
        $task = Task::factory()->create();

        $this->assertNotNull($task->column);
        $this->assertNotNull($task->column->board);
        $this->assertDatabaseCount('tasks', 1);
    }
}
