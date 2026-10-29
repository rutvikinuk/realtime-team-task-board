<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Task;
use App\Models\TaskColumn;
use Database\Seeders\BoardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_a_board(): void
    {
        $this->postJson('/api/boards', ['title' => 'Roadmap', 'description' => 'Q4'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Roadmap');

        $this->assertDatabaseHas('boards', ['title' => 'Roadmap']);
    }

    public function test_board_title_is_required_and_limited(): void
    {
        $this->postJson('/api/boards', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);

        $this->postJson('/api/boards', ['title' => str_repeat('a', 256)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_index_lists_boards_with_column_counts(): void
    {
        $board = Board::factory()->create();
        TaskColumn::factory()->count(2)->create(['board_id' => $board->id]);

        $this->getJson('/api/boards')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.columns_count', 2);
    }

    public function test_show_returns_columns_and_tasks_in_position_order(): void
    {
        $board = Board::factory()->create();
        $right = TaskColumn::factory()->create(['board_id' => $board->id, 'title' => 'Right', 'position' => 1]);
        $left = TaskColumn::factory()->create(['board_id' => $board->id, 'title' => 'Left', 'position' => 0]);
        Task::factory()->create(['task_column_id' => $left->id, 'title' => 'b', 'position' => 1]);
        Task::factory()->create(['task_column_id' => $left->id, 'title' => 'a', 'position' => 0]);

        $response = $this->getJson('/api/boards/'.$board->id)->assertOk();

        $this->assertSame(['Left', 'Right'], $response->json('data.columns.*.title'));
        $this->assertSame(['a', 'b'], $response->json('data.columns.0.tasks.*.title'));
        $this->assertSame([], $response->json('data.columns.1.tasks'));
    }

    public function test_show_returns_404_for_unknown_board(): void
    {
        $this->getJson('/api/boards/999')->assertNotFound();
    }

    public function test_can_update_a_board(): void
    {
        $board = Board::factory()->create(['title' => 'Old']);

        $this->putJson('/api/boards/'.$board->id, ['title' => 'New'])
            ->assertOk()
            ->assertJsonPath('data.title', 'New');

        $this->putJson('/api/boards/'.$board->id, ['title' => ''])
            ->assertStatus(422);
    }

    public function test_deleting_a_board_removes_columns_and_tasks(): void
    {
        $column = TaskColumn::factory()->create();
        Task::factory()->create(['task_column_id' => $column->id]);

        $this->deleteJson('/api/boards/'.$column->board_id)->assertNoContent();

        $this->assertDatabaseCount('boards', 0);
        $this->assertDatabaseCount('task_columns', 0);
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_seeder_builds_sample_board_and_is_repeatable(): void
    {
        $this->seed(BoardSeeder::class);
        $this->seed(BoardSeeder::class);

        $this->assertDatabaseCount('boards', 1);
        $this->assertDatabaseCount('task_columns', 3);
        $this->assertDatabaseCount('tasks', 7);
    }
}
