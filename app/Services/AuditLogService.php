<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Log an action to the audit logs table.
     *
     * @param int $userId
     * @param string $action
     * @param string $target
     * @param mixed $details
     * @return AuditLog
     */
    public function log(int $userId, string $action, string $target, $details = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'target' => $target,
            'details' => $details,
            'ip_address' => Request::ip()
        ]);
    }
}
