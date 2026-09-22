<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    public const STATUSES = [
        'planning' => 'Planning',
        'active' => 'Active',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
    ];

    protected $fillable = ['name', 'description', 'deadline', 'status'];

    protected function casts(): array
    {
        return ['deadline' => 'date'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function tasks(): HasMany { return $this->hasMany(Task::class); }
}
