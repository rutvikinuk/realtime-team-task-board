<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Task::query();

        if ($request->filled('task_column_id')) {
            $query->where('task_column_id', $request->input('task_column_id'));
        }

        return $query->orderBy('position')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_column_id' => ['required', 'exists:task_columns,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        // Without an explicit position the new task goes to the bottom of its column.
        if (! isset($validated['position'])) {
            $max = Task::where('task_column_id', $validated['task_column_id'])->max('position');
            $validated['position'] = $max === null ? 0 : $max + 1;
        }

        $task = Task::create($validated);

        return response()->json($task, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        return $task;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'task_column_id' => ['sometimes', 'exists:task_columns,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ]);

        $task->update($validated);

        return response()->json($task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $columnId = $task->task_column_id;

        DB::transaction(function () use ($task, $columnId) {
            $task->delete();

            // Close the gap so positions stay a gap-free 0..n-1 sequence.
            $this->renumber(
                Task::where('task_column_id', $columnId)
                    ->orderBy('position')
                    ->orderBy('id')
                    ->pluck('id')
                    ->all()
            );
        });

        return response()->json(null, 204);
    }

    /**
     * Move a task to a column at a zero-based index, then renumber the
     * affected columns so positions stay a gap-free 0..n-1 sequence.
     */
    public function move(MoveTaskRequest $request, Task $task)
    {
        $targetId = (int) $request->validated('task_column_id');
        $index = (int) $request->validated('position');
        $sourceId = $task->task_column_id;

        DB::transaction(function () use ($task, $targetId, $index, $sourceId) {
            $ids = Task::where('task_column_id', $targetId)
                ->where('id', '!=', $task->id)
                ->orderBy('position')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            array_splice($ids, min($index, count($ids)), 0, [$task->id]);

            $task->update(['task_column_id' => $targetId]);
            $this->renumber($ids);

            if ($sourceId !== $targetId) {
                $this->renumber(
                    Task::where('task_column_id', $sourceId)
                        ->orderBy('position')
                        ->orderBy('id')
                        ->pluck('id')
                        ->all()
                );
            }
        });

        return new TaskResource($task->fresh());
    }

    /**
     * @param  array<int, int>  $orderedIds
     */
    private function renumber(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            Task::whereKey($id)->update(['position' => $position]);
        }
    }
}
