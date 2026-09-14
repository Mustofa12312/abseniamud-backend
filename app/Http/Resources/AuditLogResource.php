<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'admin_name' => $this->user->name ?? 'System',
            'action' => $this->action,
            'target' => $this->target,
            'details' => $this->details,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at->format('d/m/Y H:i:s')
        ];
    }
}
