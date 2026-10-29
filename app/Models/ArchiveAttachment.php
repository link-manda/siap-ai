<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchiveAttachment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'archive_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
    ];

    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }
}
