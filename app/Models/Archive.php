<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Archive extends Model
{
    use LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    protected $fillable = [
        'document_number',
        'title',
        'document_date',
        'retention_date',
        'file_path',
        'file_size',
        'file_type',
        'category_id',
        'classification_id',
        'uploaded_by',
        'extracted_text',
        'summary',
        'ai_model',
        'ai_confidence_score',
        'delete_reason',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'retention_date' => 'date',
            'ai_confidence_score' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(Classification::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ArchiveLog::class);
    }
}
