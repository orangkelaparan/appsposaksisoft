<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AuditService
{
    public function record(string $action, string $module, ?string $recordType = null, ?int $recordId = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => session('user_id'),
            'action' => $action,
            'module' => $module,
            'record_type' => $recordType,
            'record_id' => $recordId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 1000),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
