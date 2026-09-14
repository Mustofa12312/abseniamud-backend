<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // We check if user is logged in via middleware
    }

    public function rules(): array
    {
        return [
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'current_password' => 'nullable|string|min:6|required_with:new_password',
            'new_password' => 'nullable|string|min:6|confirmed', // expects new_password_confirmation
        ];
    }
}
