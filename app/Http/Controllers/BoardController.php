<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBoardRequest;
use App\Http\Requests\UpdateBoardRequest;
use App\Http\Resources\BoardResource;
use App\Models\Board;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BoardController extends Controller
{
    /**
     * List boards (without their columns) with a column count.
     */
    public function index(): AnonymousResourceCollection
    {
        return BoardResource::collection(
            Board::query()->withCount('columns')->orderBy('id')->get()
        );
    }

    public function store(StoreBoardRequest $request): JsonResponse
    {
        $board = Board::create($request->validated());

        return (new BoardResource($board))->response()->setStatusCode(201);
    }

    /**
     * Show one board with all columns and their tasks, already in order.
     */
    public function show(Board $board): BoardResource
    {
        return new BoardResource($board->load('columns.tasks'));
    }

    public function update(UpdateBoardRequest $request, Board $board): BoardResource
    {
        $board->update($request->validated());

        return new BoardResource($board);
    }

    public function destroy(Board $board): JsonResponse
    {
        $board->delete();

        return response()->json(null, 204);
    }
}
