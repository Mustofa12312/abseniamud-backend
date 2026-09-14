<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LecturerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user->name ?? 'Unknown',
            'email' => $this->user->email ?? '-',
            'nidn' => $this->nidn ?? '-',
            'nip' => $this->nip ?? '-',
            'phone' => $this->phone ?? '-',
            'address' => $this->address ?? '-',
            'user_id' => $this->user_id
        ];
    }
}
