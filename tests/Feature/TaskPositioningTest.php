<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Task;
use App\Models\TaskColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskPositioningTest extends TestCase
{
    use RefreshDatabase;

    private function makeColumn(): TaskColumn
    {
        $board = Board::create(['title' => 'Board']);

        return TaskColumn::create(['board_id' => $board->id, 'title' => 'To Do', 'position' => 0]);
    }

    public function test_new_task_without_position_goes_to_the_bottom(): void
    {
        $column = $this->makeColumn();

        $first = $this->postJson('/api/tasks', ['task_column_id' => $column->id, 'title' => 'A'])->json();
        $second = $this->postJson('/api/tasks', ['task_column_id' => $column->id, 'title' => 'B'])->json();

        $this->assertSame(0, $first['position']);
        $this->assertSame(1, $second['position']);
    }

    public function test_explicit_position_is_respected(): void
    {
        $column = $this->makeColumn();

        $this->postJson('/api/tasks', ['task_column_id' => $column->id, 'title' => 'A', 'position' => 5])
            ->assertCreated()
            ->assertJsonFragment(['position' => 5]);
    }

    public function test_deleting_a_task_closes_the_gap(): void
    {
        $column = $this->makeColumn();
        $a = Task::create(['task_column_id' => $column->id, 'title' => 'A', 'position' => 0]);
        $b = Task::create(['task_column_id' => $column->id, 'title' => 'B', 'position' => 1]);
        $c = Task::create(['task_column_id' => $column->id, 'title' => 'C', 'position' => 2]);

        $this->deleteJson("/api/tasks/{$b->id}")->assertNoContent();

        $this->assertSame(0, $a->fresh()->position);
        $this->assertSame(1, $c->fresh()->position);
    }

    public function test_deleting_a_column_removes_its_tasks(): void
    {
        $column = $this->makeColumn();
        Task::create(['task_column_id' => $column->id, 'title' => 'A', 'position' => 0]);

        $this->deleteJson("/api/columns/{$column->id}")->assertNoContent();

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_blank_title_is_rejected_on_update(): void
    {
        $column = $this->makeColumn();
        $task = Task::create(['task_column_id' => $column->id, 'title' => 'A', 'position' => 0]);

        $this->patchJson("/api/tasks/{$task->id}", ['title' => ''])->assertUnprocessable();
    }
}
