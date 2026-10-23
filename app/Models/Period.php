<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Period extends Model
{
    protected $fillable = [
        'name', 'start_date', 'end_date', 'wage_limit', 'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'wage_limit' => 'integer',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
