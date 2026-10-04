<?php

namespace App\Services\Native;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogger
{
    public function __construct(private readonly Request $request) {}

    public function record(User $actor, string $action, Model $resource): AuditLog
    {
        return AuditLog::create([
            'tenant_id' => $actor->tenant_id,
            'user_id' => $actor->id,
            'action' => $action,
            'resource_type' => class_basename($resource),
            'resource_id' => $resource->getKey(),
            'resource_uuid' => $resource->getAttribute('uuid'),
            'ip_address' => $this->request->ip(),
            'user_agent' => Str::limit((string) $this->request->userAgent(), 500, ''),
        ]);
    }
}
