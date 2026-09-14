<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lecturer_id' => $this->lecturer_id,
            'lecturer_name' => $this->lecturer->user->name ?? '-',
            'position_id' => $this->position_id,
            'position_name' => $this->position->name ?? '-',
            'is_primary' => (bool) $this->is_primary,
        ];
    }
}
