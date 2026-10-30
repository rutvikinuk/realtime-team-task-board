<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\TaskColumnController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('boards', BoardController::class);
Route::apiResource('columns', TaskColumnController::class)->parameters(['columns' => 'taskColumn']);
Route::patch('tasks/{task}/move', [TaskController::class, 'move']);
Route::apiResource('tasks', TaskController::class);
