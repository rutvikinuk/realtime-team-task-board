<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Task;
use App\Models\TaskColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskColumnApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_appends_column_to_the_end_when_no_position_given(): void
    {
        $board = Board::factory()->create();

        $this->postJson('/api/columns', ['board_id' => $board->id, 'title' => 'To Do'])
            ->assertCreated()
            ->assertJsonPath('data.position', 0);

        $this->postJson('/api/columns', ['board_id' => $board->id, 'title' => 'Doing'])
            ->assertCreated()
            ->assertJsonPath('data.position', 1);
    }

    public function test_explicit_position_is_respected(): void
    {
        $board = Board::factory()->create();

        $this->postJson('/api/columns', ['board_id' => $board->id, 'title' => 'X', 'position' => 5])
            ->assertCreated()
            ->assertJsonPath('data.position', 5);
    }

    public function test_create_validates_input(): void
    {
        $this->postJson('/api/columns', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['board_id', 'title']);

        $this->postJson('/api/columns', ['board_id' => 999, 'title' => 'x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['board_id']);

        $board = Board::factory()->create();
        $this->postJson('/api/columns', ['board_id' => $board->id, 'title' => 'x', 'position' => -1])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['position']);
    }

    public function test_index_filters_by_board_and_orders_by_position(): void
    {
        $a = Board::factory()->create();
        $b = Board::factory()->create();
        TaskColumn::factory()->create(['board_id' => $a->id, 'title' => 'second', 'position' => 1]);
        TaskColumn::factory()->create(['board_id' => $a->id, 'title' => 'first', 'position' => 0]);
        TaskColumn::factory()->create(['board_id' => $b->id, 'title' => 'other', 'position' => 0]);

        $titles = $this->getJson('/api/columns?board_id='.$a->id)->assertOk()->json('data.*.title');

        $this->assertSame(['first', 'second'], $titles);
    }

    public function test_show_includes_tasks(): void
    {
        $column = TaskColumn::factory()->create();
        Task::factory()->create(['task_column_id' => $column->id, 'title' => 'Card']);

        $this->getJson('/api/columns/'.$column->id)
            ->assertOk()
            ->assertJsonPath('data.tasks.0.title', 'Card');
    }

    public function test_can_rename_and_reorder_but_not_change_board(): void
    {
        $column = TaskColumn::factory()->create(['title' => 'Old', 'position' => 0]);
        $other = Board::factory()->create();

        $this->putJson('/api/columns/'.$column->id, [
            'title' => 'New',
            'position' => 4,
            'board_id' => $other->id,
        ])->assertOk()->assertJsonPath('data.title', 'New');

        $this->assertDatabaseHas('task_columns', [
            'id' => $column->id,
            'title' => 'New',
            'position' => 4,
            'board_id' => $column->board_id,
        ]);
    }

    public function test_delete_column_cascades_to_tasks(): void
    {
        $column = TaskColumn::factory()->create();
        Task::factory()->create(['task_column_id' => $column->id]);

        $this->deleteJson('/api/columns/'.$column->id)->assertNoContent();

        $this->assertDatabaseCount('task_columns', 0);
        $this->assertDatabaseCount('tasks', 0);
    }
}
