<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CorrectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user->name ?? 'Unknown',
            'date' => \Carbon\Carbon::parse($this->date)->translatedFormat('d M Y'),
            'raw_date' => $this->date,
            'type' => $this->type,
            'reason' => $this->reason,
            'status' => $this->status,
            'submitted_at' => $this->created_at->format('d/m/Y H:i'),
            'user_id' => $this->user_id
        ];
    }
}
