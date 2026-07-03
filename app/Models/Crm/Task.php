<?php

namespace App\Models\Crm;

use App\Enums\Crm\TaskPriority;
use App\Enums\Crm\TaskStatus;
use App\Models\Crm\Concerns\BelongsToCrmContact;
use App\Models\User;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'contact_type',
    'contact_id',
    'user_id',
    'business_line',
    'title',
    'description',
    'priority',
    'status',
    'due_at',
    'completed_at',
    'reminder_at',
])]
class Task extends Model
{
    use BelongsToCrmContact;
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'business_line' => \App\Enums\BusinessLine::class,
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'reminder_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calendarEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'task_id');
    }

    protected static function newFactory(): TaskFactory
    {
        return TaskFactory::new();
    }
}
