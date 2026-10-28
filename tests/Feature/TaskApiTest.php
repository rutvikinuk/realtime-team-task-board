<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Task;
use App\Models\TaskColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeColumn(string $title = 'To Do'): TaskColumn
    {
        $board = Board::create(['title' => 'Sprint board']);

        return TaskColumn::create(['board_id' => $board->id, 'title' => $title, 'position' => 0]);
    }

    public function test_health_endpoint_is_up(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_can_create_a_task(): void
    {
        $column = $this->makeColumn();

        $this->postJson('/api/tasks', [
            'task_column_id' => $column->id,
            'title' => 'Write audit',
        ])->assertCreated()->assertJsonFragment(['title' => 'Write audit']);

        $this->assertDatabaseHas('tasks', ['title' => 'Write audit', 'task_column_id' => $column->id]);
    }

    public function test_create_requires_title_and_valid_column(): void
    {
        $this->postJson('/api/tasks', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'task_column_id']);

        $this->postJson('/api/tasks', ['title' => 'x', 'task_column_id' => 999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['task_column_id']);
    }

    public function test_index_filters_by_column_and_orders_by_position(): void
    {
        $a = $this->makeColumn('A');
        $b = $this->makeColumn('B');
        Task::create(['task_column_id' => $a->id, 'title' => 'second', 'position' => 2]);
        Task::create(['task_column_id' => $a->id, 'title' => 'first', 'position' => 1]);
        Task::create(['task_column_id' => $b->id, 'title' => 'other', 'position' => 1]);

        $titles = $this->getJson('/api/tasks?task_column_id='.$a->id)
            ->assertOk()
            ->json('*.title');

        $this->assertSame(['first', 'second'], $titles);
    }

    public function test_can_update_and_move_a_task(): void
    {
        $a = $this->makeColumn('A');
        $b = $this->makeColumn('B');
        $task = Task::create(['task_column_id' => $a->id, 'title' => 'Move me', 'position' => 0]);

        $this->putJson('/api/tasks/'.$task->id, ['task_column_id' => $b->id, 'position' => 3])
            ->assertOk();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'task_column_id' => $b->id, 'position' => 3]);
    }

    public function test_can_delete_a_task(): void
    {
        $column = $this->makeColumn();
        $task = Task::create(['task_column_id' => $column->id, 'title' => 'Bye']);

        $this->deleteJson('/api/tasks/'.$task->id)->assertSuccessful();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_deleting_a_column_cascades_to_its_tasks(): void
    {
        $column = $this->makeColumn();
        Task::create(['task_column_id' => $column->id, 'title' => 'Child']);

        $column->delete();

        $this->assertDatabaseCount('tasks', 0);
    }
}
