<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskColumnRequest;
use App\Http\Requests\UpdateTaskColumnRequest;
use App\Http\Resources\TaskColumnResource;
use App\Models\TaskColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskColumnController extends Controller
{
    /**
     * List columns, optionally for one board (?board_id=), ordered by position.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TaskColumn::query();

        if ($request->filled('board_id')) {
            $query->where('board_id', $request->input('board_id'));
        }

        return TaskColumnResource::collection($query->orderBy('position')->orderBy('id')->get());
    }

    /**
     * Create a column. Without an explicit position it goes to the far right.
     */
    public function store(StoreTaskColumnRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! isset($data['position'])) {
            $max = TaskColumn::where('board_id', $data['board_id'])->max('position');
            $data['position'] = $max === null ? 0 : $max + 1;
        }

        $column = TaskColumn::create($data);

        return (new TaskColumnResource($column))->response()->setStatusCode(201);
    }

    public function show(TaskColumn $taskColumn): TaskColumnResource
    {
        return new TaskColumnResource($taskColumn->load('tasks'));
    }

    public function update(UpdateTaskColumnRequest $request, TaskColumn $taskColumn): TaskColumnResource
    {
        $taskColumn->update($request->validated());

        return new TaskColumnResource($taskColumn);
    }

    public function destroy(TaskColumn $taskColumn): JsonResponse
    {
        $taskColumn->delete();

        return response()->json(null, 204);
    }
}
