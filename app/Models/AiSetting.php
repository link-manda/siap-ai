<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class AiSetting extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'provider',
        'base_url',
        'api_key',
        'model',
        'timeout',
        'is_active',
    ];

    /**
     * Attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'api_key',
    ];

    /**
     * Mutator to sanitize base_url and strip sensitive query parameters.
     */
    public function setBaseUrlAttribute(?string $value): void
    {
        if (empty($value)) {
            $this->attributes['base_url'] = null;

            return;
        }

        $parsed = parse_url($value);
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
            $sensitiveKeys = ['key', 'token', 'api_key', 'apikey', 'secret'];
            foreach ($sensitiveKeys as $k) {
                unset($queryParams[$k]);
            }
            $cleanQuery = http_build_query($queryParams);
            $scheme = isset($parsed['scheme']) ? $parsed['scheme'].'://' : '';
            $host = $parsed['host'] ?? '';
            $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
            $path = $parsed['path'] ?? '';
            $value = $scheme.$host.$port.$path.($cleanQuery ? '?'.$cleanQuery : '');
        }

        $this->attributes['base_url'] = rtrim($value, '/');
    }

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'timeout' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['provider', 'base_url', 'model', 'timeout', 'is_active'])
            ->logOnlyDirty();
    }
}
