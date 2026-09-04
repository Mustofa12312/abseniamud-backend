<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'required|numeric',
            'location_id' => 'nullable|exists:locations,id', // Optional if auto-detecting
        ];
    }
    
    public function messages(): array
    {
        return [
            'latitude.required' => 'Koordinat lintang (latitude) wajib disertakan.',
            'longitude.required' => 'Koordinat bujur (longitude) wajib disertakan.',
            'accuracy.required' => 'Akurasi GPS wajib disertakan.',
        ];
    }
}
