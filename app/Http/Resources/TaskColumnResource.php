<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TaskColumn */
class TaskColumnResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'board_id' => $this->board_id,
            'title' => $this->title,
            'position' => $this->position,
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
        ];
    }
}
