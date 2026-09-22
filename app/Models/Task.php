<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Task extends Model
{
    public const STATUSES = ['todo' => 'To-do', 'in_progress' => 'In Progress', 'done' => 'Done'];
    public const PRIORITIES = ['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'];
    public const CATEGORIES = ['organization' => 'Organization', 'academic' => 'Academic', 'development' => 'Development', 'personal' => 'Personal'];
    protected $fillable = ['project_id', 'title', 'description', 'status', 'priority', 'category', 'due_date', 'completed_at', 'position'];
    protected function casts(): array { return ['due_date' => 'date', 'completed_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function attachments(): HasMany { return $this->hasMany(TaskAttachment::class); }
    public function isOverdue(): bool { return $this->status !== 'done' && $this->due_date?->lt(today()); }
}
