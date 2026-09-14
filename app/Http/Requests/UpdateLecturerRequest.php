<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Lecturer;

class UpdateLecturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $lecturer = Lecturer::findOrFail($this->route('id'));
        $user = $lecturer->user;
        $userId = $user ? $user->id : '';

        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $userId,
            'password' => 'nullable|string|min:6', // Optional for update
            'nidn' => 'nullable|string',
            'nip' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ];
    }
}
