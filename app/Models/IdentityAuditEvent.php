<?php

namespace App\Models;

use App\Enums\IdentityAuditEventType;
use Database\Factories\IdentityAuditEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityAuditEvent extends Model
{
    /** @use HasFactory<IdentityAuditEventFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'event',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => IdentityAuditEventType::class,
            'metadata' => 'array',
        ];
    }
}
