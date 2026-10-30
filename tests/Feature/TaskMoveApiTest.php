<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Task;
use App\Models\TaskColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskMoveApiTest extends TestCase
{
    use RefreshDatabase;

    private TaskColumn $todo;

    private TaskColumn $done;

    protected function setUp(): void
    {
        parent::setUp();
        $board = Board::create(['title' => 'Board']);
        $this->todo = TaskColumn::create(['board_id' => $board->id, 'title' => 'To Do', 'position' => 0]);
        $this->done = TaskColumn::create(['board_id' => $board->id, 'title' => 'Done', 'position' => 1]);
    }

    /** @return array<int, Task> */
    private function tasks(TaskColumn $column, int $count): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = Task::create(['task_column_id' => $column->id, 'title' => "{$column->title} {$i}", 'position' => $i]);
        }

        return $out;
    }

    /** @return array<int, int> */
    private function order(TaskColumn $column): array
    {
        return Task::where('task_column_id', $column->id)->orderBy('position')->pluck('id')->all();
    }

    public function test_move_to_another_column_renumbers_both_columns(): void
    {
        [$a, $b, $c] = $this->tasks($this->todo, 3);
        [$x, $y] = $this->tasks($this->done, 2);

        $this->patchJson("/api/tasks/{$b->id}/move", ['task_column_id' => $this->done->id, 'position' => 1])
            ->assertOk()
            ->assertJsonPath('data.task_column_id', $this->done->id)
            ->assertJsonPath('data.position', 1);

        $this->assertSame([$a->id, $c->id], $this->order($this->todo));
        $this->assertSame([$x->id, $b->id, $y->id], $this->order($this->done));
        $this->assertSame([0, 1], Task::where('task_column_id', $this->todo->id)->orderBy('position')->pluck('position')->all());
    }

    public function test_reorder_within_the_same_column(): void
    {
        [$a, $b, $c] = $this->tasks($this->todo, 3);

        $this->patchJson("/api/tasks/{$a->id}/move", ['task_column_id' => $this->todo->id, 'position' => 2])->assertOk();

        $this->assertSame([$b->id, $c->id, $a->id], $this->order($this->todo));
    }

    public function test_position_beyond_the_end_is_clamped(): void
    {
        [$a] = $this->tasks($this->todo, 1);
        [$x] = $this->tasks($this->done, 1);

        $this->patchJson("/api/tasks/{$a->id}/move", ['task_column_id' => $this->done->id, 'position' => 99])
            ->assertOk()->assertJsonPath('data.position', 1);

        $this->assertSame([$x->id, $a->id], $this->order($this->done));
    }

    public function test_move_into_an_empty_column(): void
    {
        [$a] = $this->tasks($this->todo, 1);

        $this->patchJson("/api/tasks/{$a->id}/move", ['task_column_id' => $this->done->id, 'position' => 0])->assertOk();

        $this->assertSame([$a->id], $this->order($this->done));
        $this->assertSame([], $this->order($this->todo));
    }

    public function test_move_validates_input(): void
    {
        [$a] = $this->tasks($this->todo, 1);

        $this->patchJson("/api/tasks/{$a->id}/move", [])
            ->assertStatus(422)->assertJsonValidationErrors(['task_column_id', 'position']);
        $this->patchJson("/api/tasks/{$a->id}/move", ['task_column_id' => 9999, 'position' => -1])
            ->assertStatus(422)->assertJsonValidationErrors(['task_column_id', 'position']);
    }

    public function test_move_unknown_task_is_404(): void
    {
        $this->patchJson('/api/tasks/9999/move', ['task_column_id' => $this->todo->id, 'position' => 0])->assertNotFound();
    }
}
