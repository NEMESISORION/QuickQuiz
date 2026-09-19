<?php

namespace App\Domain\Identity;

use App\Enums\IdentityAuditEventType;
use App\Models\IdentityAuditEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IdentityAuditLogger
{
    public function __construct(private Request $request) {}

    /**
     * @param  array<string, bool|int|string|null>  $metadata
     */
    public function record(
        IdentityAuditEventType $event,
        ?User $user = null,
        array $metadata = [],
    ): IdentityAuditEvent {
        $userAgent = $this->request->userAgent();

        return IdentityAuditEvent::create([
            'user_id' => $user?->getKey(),
            'event' => $event,
            'ip_address' => $this->request->ip(),
            'user_agent' => is_string($userAgent) ? Str::limit($userAgent, 1024, '') : null,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}
