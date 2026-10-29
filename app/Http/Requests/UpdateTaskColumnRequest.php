<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskColumnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A column cannot be moved to another board, only renamed or reordered.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
