<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
            'radius' => (int) $this->radius,
            'accuracy' => (int) $this->max_accuracy,
            'is_active' => (bool) $this->is_active,
            'status' => $this->is_active ? 'Aktif' : 'Nonaktif',
        ];
    }
}
