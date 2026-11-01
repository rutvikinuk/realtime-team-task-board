<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Task;
use App\Models\TaskColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Edge cases and error paths for all API endpoints: bad input must give a
 * 422 (never a 500), unknown ids a 404, and nothing may be saved on failure.
 */
class ValidationEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private Board $board;

    private TaskColumn $column;

    protected function setUp(): void
    {
        parent::setUp();
        $this->board = Board::create(['title' => 'Board']);
        $this->column = TaskColumn::create(['board_id' => $this->board->id, 'title' => 'To Do', 'position' => 0]);
    }

    private function task(int $position = 0): Task
    {
        return Task::create(['task_column_id' => $this->column->id, 'title' => 'T'.$position, 'position' => $position]);
    }

    // ---- tasks: create ----

    public function test_task_title_over_255_characters_is_rejected(): void
    {
        $this->postJson('/api/tasks', ['task_column_id' => $this->column->id, 'title' => str_repeat('a', 256)])
            ->assertStatus(422)->assertJsonValidationErrors('title');
        $this->assertSame(0, Task::count());
    }

    public function test_task_title_of_exactly_255_characters_is_accepted(): void
    {
        $this->postJson('/api/tasks', ['task_column_id' => $this->column->id, 'title' => str_repeat('a', 255)])
            ->assertCreated();
    }

    public function test_task_create_rejects_negative_or_non_integer_position(): void
    {
        $base = ['task_column_id' => $this->column->id, 'title' => 'x'];
        $this->postJson('/api/tasks', $base + ['position' => -1])->assertStatus(422)->assertJsonValidationErrors('position');
        $this->postJson('/api/tasks', $base + ['position' => 'top'])->assertStatus(422)->assertJsonValidationErrors('position');
        $this->assertSame(0, Task::count());
    }

    public function test_task_create_requires_a_column(): void
    {
        $this->postJson('/api/tasks', ['title' => 'orphan'])
            ->assertStatus(422)->assertJsonValidationErrors('task_column_id');
    }

    public function test_task_create_rejects_a_non_string_description(): void
    {
        $this->postJson('/api/tasks', ['task_column_id' => $this->column->id, 'title' => 'x', 'description' => ['a']])
            ->assertStatus(422)->assertJsonValidationErrors('description');
    }

    public function test_task_description_is_optional_and_nullable(): void
    {
        $this->postJson('/api/tasks', ['task_column_id' => $this->column->id, 'title' => 'x', 'description' => null])
            ->assertCreated()->assertJsonPath('description', null);
    }

    // ---- tasks: update / show / delete ----

    public function test_task_update_rejects_unknown_column_long_title_and_negative_position(): void
    {
        $task = $this->task();
        $this->patchJson("/api/tasks/{$task->id}", ['task_column_id' => 9999])->assertStatus(422)->assertJsonValidationErrors('task_column_id');
        $this->patchJson("/api/tasks/{$task->id}", ['title' => str_repeat('a', 256)])->assertStatus(422)->assertJsonValidationErrors('title');
        $this->patchJson("/api/tasks/{$task->id}", ['position' => -3])->assertStatus(422)->assertJsonValidationErrors('position');
        $this->assertSame('T0', $task->fresh()->title);
    }

    public function test_task_update_can_clear_the_description(): void
    {
        $task = Task::create(['task_column_id' => $this->column->id, 'title' => 'x', 'description' => 'old', 'position' => 0]);
        $this->patchJson("/api/tasks/{$task->id}", ['description' => null])->assertOk();
        $this->assertNull($task->fresh()->description);
    }

    public function test_task_show_returns_the_task_and_404_for_unknown(): void
    {
        $task = $this->task();
        $this->getJson("/api/tasks/{$task->id}")->assertOk()->assertJsonPath('title', 'T0');
        $this->getJson('/api/tasks/9999')->assertNotFound();
    }

    public function test_task_update_and_delete_of_unknown_task_are_404(): void
    {
        $this->patchJson('/api/tasks/9999', ['title' => 'x'])->assertNotFound();
        $this->deleteJson('/api/tasks/9999')->assertNotFound();
    }

    public function test_task_index_without_filter_returns_every_task(): void
    {
        $other = TaskColumn::create(['board_id' => $this->board->id, 'title' => 'Done', 'position' => 1]);
        $this->task(0);
        Task::create(['task_column_id' => $other->id, 'title' => 'D', 'position' => 0]);
        $this->getJson('/api/tasks')->assertOk()->assertJsonCount(2);
    }

    // ---- move ----

    public function test_move_rejects_negative_non_integer_and_missing_fields(): void
    {
        $task = $this->task();
        $url = "/api/tasks/{$task->id}/move";
        $this->patchJson($url, ['task_column_id' => $this->column->id, 'position' => -1])->assertStatus(422)->assertJsonValidationErrors('position');
        $this->patchJson($url, ['task_column_id' => $this->column->id, 'position' => 'first'])->assertStatus(422)->assertJsonValidationErrors('position');
        $this->patchJson($url, ['task_column_id' => 9999, 'position' => 0])->assertStatus(422)->assertJsonValidationErrors('task_column_id');
        $this->patchJson($url, [])->assertStatus(422)->assertJsonValidationErrors(['task_column_id', 'position']);
    }

    public function test_failed_move_leaves_the_task_untouched(): void
    {
        $task = $this->task(4);
        $this->patchJson("/api/tasks/{$task->id}/move", ['task_column_id' => 9999, 'position' => 0])->assertStatus(422);
        $task->refresh();
        $this->assertSame($this->column->id, $task->task_column_id);
        $this->assertSame(4, $task->position);
    }

    public function test_move_response_has_the_resource_shape(): void
    {
        $task = $this->task();
        $this->patchJson("/api/tasks/{$task->id}/move", ['task_column_id' => $this->column->id, 'position' => 0])
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'task_column_id', 'title', 'description', 'position', 'created_at', 'updated_at']])
            ->assertJsonPath('data.id', $task->id);
    }

    public function test_moving_a_task_to_its_current_spot_changes_nothing(): void
    {
        $a = $this->task(0);
        $b = $this->task(1);
        $c = $this->task(2);
        $this->patchJson("/api/tasks/{$b->id}/move", ['task_column_id' => $this->column->id, 'position' => 1])->assertOk();
        $this->assertSame(
            [$a->id, $b->id, $c->id],
            Task::where('task_column_id', $this->column->id)->orderBy('position')->pluck('id')->all()
        );
    }

    public function test_move_heals_gaps_in_the_target_column(): void
    {
        // Positions 0, 5, 9 (gappy): any move renumbers the column to 0..n-1.
        $a = $this->task(0);
        $b = $this->task(5);
        $c = $this->task(9);
        $this->patchJson("/api/tasks/{$c->id}/move", ['task_column_id' => $this->column->id, 'position' => 0])->assertOk();
        $this->assertEquals([$c->id => 0, $a->id => 1, $b->id => 2], Task::pluck('position', 'id')->all());
    }

    // ---- boards ----

    public function test_board_description_limit_and_nullable(): void
    {
        $this->postJson('/api/boards', ['title' => 'B', 'description' => str_repeat('a', 5001)])
            ->assertStatus(422)->assertJsonValidationErrors('description');
        $this->postJson('/api/boards', ['title' => 'B', 'description' => null])->assertCreated();
    }

    public function test_board_update_rejects_a_blank_title(): void
    {
        $this->patchJson("/api/boards/{$this->board->id}", ['title' => ''])
            ->assertStatus(422)->assertJsonValidationErrors('title');
        $this->assertSame('Board', $this->board->fresh()->title);
    }

    public function test_board_update_without_title_keeps_the_old_one(): void
    {
        $this->patchJson("/api/boards/{$this->board->id}", ['description' => 'new'])->assertOk();
        $this->assertSame('Board', $this->board->fresh()->title);
        $this->assertSame('new', $this->board->fresh()->description);
    }

    public function test_board_update_and_delete_of_unknown_board_are_404(): void
    {
        $this->patchJson('/api/boards/9999', ['title' => 'x'])->assertNotFound();
        $this->deleteJson('/api/boards/9999')->assertNotFound();
    }

    public function test_deleting_a_board_returns_204(): void
    {
        $this->deleteJson("/api/boards/{$this->board->id}")->assertNoContent();
        $this->assertSame(0, Board::count());
    }

    public function test_board_index_is_empty_when_there_are_no_boards(): void
    {
        Board::query()->delete();
        $this->getJson('/api/boards')->assertOk()->assertExactJson(['data' => []]);
    }

    // ---- columns ----

    public function test_column_create_rejects_unknown_board_long_title_and_negative_position(): void
    {
        $this->postJson('/api/columns', ['board_id' => 9999, 'title' => 'x'])->assertStatus(422)->assertJsonValidationErrors('board_id');
        $this->postJson('/api/columns', ['board_id' => $this->board->id, 'title' => str_repeat('a', 256)])->assertStatus(422)->assertJsonValidationErrors('title');
        $this->postJson('/api/columns', ['board_id' => $this->board->id, 'title' => 'x', 'position' => -1])->assertStatus(422)->assertJsonValidationErrors('position');
        $this->assertSame(1, TaskColumn::count());
    }

    public function test_column_update_rejects_blank_title_and_negative_position(): void
    {
        $this->patchJson("/api/columns/{$this->column->id}", ['title' => ''])->assertStatus(422)->assertJsonValidationErrors('title');
        $this->patchJson("/api/columns/{$this->column->id}", ['position' => -2])->assertStatus(422)->assertJsonValidationErrors('position');
        $this->assertSame('To Do', $this->column->fresh()->title);
    }

    public function test_column_show_update_and_delete_of_unknown_column_are_404(): void
    {
        $this->getJson('/api/columns/9999')->assertNotFound();
        $this->patchJson('/api/columns/9999', ['title' => 'x'])->assertNotFound();
        $this->deleteJson('/api/columns/9999')->assertNotFound();
    }

    public function test_deleting_a_column_returns_204_and_it_is_gone(): void
    {
        $this->deleteJson("/api/columns/{$this->column->id}")->assertNoContent();
        $this->getJson("/api/columns/{$this->column->id}")->assertNotFound();
    }

    public function test_unknown_api_route_is_a_json_404(): void
    {
        $this->getJson('/api/does-not-exist')->assertNotFound();
    }
}
