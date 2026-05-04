<?php

use App\Models\ActivityLog;

if (! function_exists('logActivity')) {
    function logActivity(
        string $action,
        ?string $description = null,
        ?string $module = null,
        int|string|null $referenceId = null,
        ?array $changes = null
    ): void {
        if (! auth()->check()) {
            return;
        }

        $request = app()->bound('request') ? request() : null;

        ActivityLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => $module,
            'reference_id' => $referenceId,
            'description' => $description,
            'changes' => $changes,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
