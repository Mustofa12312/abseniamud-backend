<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware already handles authorization
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|numeric',
            'max_accuracy' => 'required|numeric',
            'is_active' => 'boolean'
        ];
    }
}
