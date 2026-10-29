<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Board */
class BoardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'columns_count' => $this->whenCounted('columns'),
            'columns' => TaskColumnResource::collection($this->whenLoaded('columns')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
