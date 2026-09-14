<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLecturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'nidn' => 'nullable|string',
            'nip' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ];
    }
}
