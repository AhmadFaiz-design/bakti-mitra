<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'partner_id', 'period_id', 'created_by', 'title', 'description',
        'wage_amount', 'due_date', 'progress', 'status', 'assigned_at',
        'completed_at',
    ];

    protected $casts = [
        'wage_amount' => 'integer',
        'due_date' => 'date',
        'progress' => 'integer',
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class);
    }
}
