<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'user_id', 'partner_code', 'phone', 'address',
        'assigned_coordinator_id', 'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_coordinator_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
