<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveLog extends Model
{
    const UPDATED_AT = null; // No updated_at for logs

    protected $fillable = [
        'archive_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
    ];

    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
