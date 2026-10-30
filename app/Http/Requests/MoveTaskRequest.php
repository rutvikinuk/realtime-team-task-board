<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'task_column_id' => ['required', 'integer', 'exists:task_columns,id'],
            'position' => ['required', 'integer', 'min:0'],
        ];
    }
}
