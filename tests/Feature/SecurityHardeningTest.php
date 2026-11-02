<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\TaskColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function makeColumn(): TaskColumn
    {
        $board = Board::create(['title' => 'Sec board']);

        return TaskColumn::create(['board_id' => $board->id, 'title' => 'To Do', 'position' => 0]);
    }

    public function test_security_headers_are_sent_on_api_and_health_responses(): void
    {
        foreach (['/api/boards', '/up'] as $url) {
            $this->get($url)
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'DENY')
                ->assertHeader('Referrer-Policy', 'no-referrer')
                ->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
        }
    }

    public function test_api_is_rate_limited_per_client(): void
    {
        config(['security.api_rate_limit' => 3]);

        for ($i = 0; $i < 3; $i++) {
            $this->getJson('/api/boards')->assertOk();
        }

        $this->getJson('/api/boards')->assertStatus(429);
    }

    public function test_cors_allows_the_frontend_origin_only(): void
    {
        $this->withHeaders(['Origin' => 'http://localhost:5173'])
            ->getJson('/api/boards')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');

        $response = $this->withHeaders(['Origin' => 'https://evil.example'])->getJson('/api/boards');
        $response->assertOk();
        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function test_cors_preflight_for_a_disallowed_origin_gets_no_allow_header(): void
    {
        $response = $this->call('OPTIONS', '/api/tasks', [], [], [], [
            'HTTP_ORIGIN' => 'https://evil.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function test_task_description_is_length_limited(): void
    {
        $column = $this->makeColumn();

        $this->postJson('/api/tasks', [
            'task_column_id' => $column->id,
            'title' => 'T',
            'description' => str_repeat('a', 10001),
        ])->assertStatus(422)->assertJsonValidationErrors('description');

        $this->postJson('/api/tasks', [
            'task_column_id' => $column->id,
            'title' => 'T',
            'description' => str_repeat('a', 10000),
        ])->assertCreated();
    }

    public function test_absurdly_large_positions_are_rejected(): void
    {
        $column = $this->makeColumn();

        $this->postJson('/api/tasks', [
            'task_column_id' => $column->id, 'title' => 'T', 'position' => 1000001,
        ])->assertStatus(422)->assertJsonValidationErrors('position');

        $this->postJson('/api/columns', [
            'board_id' => $column->board_id, 'title' => 'C', 'position' => 9223372036854775807,
        ])->assertStatus(422)->assertJsonValidationErrors('position');
    }

    public function test_filters_must_be_integers(): void
    {
        $this->getJson('/api/tasks?task_column_id=abc')
            ->assertStatus(422)->assertJsonValidationErrors('task_column_id');
        $this->getJson('/api/columns?board_id=1%20OR%201=1')
            ->assertStatus(422)->assertJsonValidationErrors('board_id');
    }

    public function test_sql_metacharacters_in_titles_are_stored_literally(): void
    {
        $column = $this->makeColumn();
        $evil = "Robert'); DROP TABLE tasks;-- <script>alert(1)</script>";

        $this->postJson('/api/tasks', ['task_column_id' => $column->id, 'title' => $evil])
            ->assertCreated()->assertJsonPath('title', $evil);

        $this->assertDatabaseHas('tasks', ['title' => $evil]);
    }
}
