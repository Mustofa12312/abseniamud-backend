<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lecturer_id' => 'required|exists:lecturers,id',
            'position_id' => 'required|exists:positions,id',
            'is_primary' => 'boolean'
        ];
    }
}
