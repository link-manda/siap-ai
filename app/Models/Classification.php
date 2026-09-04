<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classification extends Model
{
    protected $fillable = [
        'name',
        'level',
    ];

    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class);
    }
}
