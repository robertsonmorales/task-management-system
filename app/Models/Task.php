<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $table = 'tasks';

    protected $fillable = [
        'task_name',
        'task_description',
        'due_date',
        'priority',
        'status',
        'user_id',
        'assign_to',
    ];

    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Tasks that are not completed yet.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', 'Completed');
    }

    /**
     * Open tasks whose due date has passed.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->where('due_date', '<', Carbon::today()->toDateString());
    }

    /**
     * Limit tasks to a named due date range, e.g. "today" or "this_week". Unrecognized ranges are ignored.
     */
    public function scopeDue(Builder $query, ?string $range): Builder
    {
        $today = Carbon::today();

        return match ($range) {
            'overdue' => $query->overdue(),
            'today' => $query->whereDate('due_date', $today),
            'tomorrow' => $query->whereDate('due_date', $today->copy()->addDay()),
            'next_7_days' => $query->whereBetween('due_date', [$today->toDateString(), $today->copy()->addDays(7)->toDateString()]),
            'this_week' => $query->whereBetween('due_date', [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()]),
            'next_week' => $query->whereBetween('due_date', [$today->copy()->addWeek()->startOfWeek()->toDateString(), $today->copy()->addWeek()->endOfWeek()->toDateString()]),
            'this_month' => $query->whereBetween('due_date', [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()]),
            'next_month' => $query->whereBetween('due_date', [$today->copy()->addMonthNoOverflow()->startOfMonth()->toDateString(), $today->copy()->addMonthNoOverflow()->endOfMonth()->toDateString()]),
            default => $query,
        };
    }

    /**
     * Order tasks from Urgent down to Low priority.
     */
    public function scopeOrderByPriorityDesc(Builder $query): Builder
    {
        return $query->orderByRaw("CASE priority WHEN 'Urgent' THEN 0 WHEN 'High' THEN 1 WHEN 'Normal' THEN 2 ELSE 3 END");
    }

    /**
     * Limit tasks to one assignee, or to tasks nobody is assigned to when given "unassigned".
     */
    public function scopeAssignedTo(Builder $query, string|int $assignee): Builder
    {
        return $assignee === 'unassigned'
            ? $query->whereNull('assign_to')
            : $query->where('assign_to', (int) $assignee);
    }

    /**
     * Admins see every task; everyone else only sees the tasks assigned to them.
     */
    public function scopeMyTasks($query)
    {
        if (auth()->user()?->isAdmin()) {
            return $query;
        }

        return $query->where('assign_to', auth()->id());
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assign_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The shape the task lists and task detail view expect. Requires the assignee and creator relations.
     *
     * @return array{id: int, task_name: string, task_description: ?string, due_date: string, priority: string, status: string, assign_to: ?array{id: int, name: string}, created_by: ?array{id: int, name: string}}
     */
    public function toListItem(): array
    {
        return [
            'id' => $this->id,
            'task_name' => $this->task_name,
            'task_description' => $this->task_description,
            'due_date' => Carbon::parse($this->due_date)->format('m/d/Y'),
            'priority' => $this->priority,
            'status' => $this->status,
            'assign_to' => $this->assignee?->only(['id', 'name']),
            'created_by' => $this->creator?->only(['id', 'name']),
        ];
    }
}
