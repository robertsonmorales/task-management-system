<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Response;

class DashboardController extends Controller
{
    protected static int $ATTENTION_LIMIT = 8;

    protected static int $MY_TASKS_LIMIT = 5;

    protected static array $HIGH_PRIORITIES = ['High', 'Urgent'];

    /**
     * The regular user's work queue. Admins have their own oversight dashboard.
     */
    public function dashboard(Request $request): Response|RedirectResponse
    {
        if ($request->user()->isAdmin()) {
            return to_route('admin-dashboard');
        }

        $today = Carbon::today()->toDateString();

        $attentionTasks = $this->needsAttention($this->openTasks(), $today)
            ->orderByRaw('CASE WHEN due_date < ? THEN 0 WHEN due_date = ? THEN 1 ELSE 2 END', [$today, $today])
            ->orderBy('due_date')
            ->orderByPriorityDesc()
            ->limit(self::$ATTENTION_LIMIT)
            ->get();

        $myTasks = $this->openTasks()
            ->whereNotIn('id', $attentionTasks->pluck('id'))
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->limit(self::$MY_TASKS_LIMIT)
            ->get();

        return inertia('Dashboard', [
            'summary' => [
                'overdue' => $this->openTasks()->overdue()->count(),
                'dueToday' => $this->openTasks()->due('today')->count(),
                'upcoming' => $this->openTasks()->whereBetween('due_date', [
                    Carbon::tomorrow()->toDateString(),
                    Carbon::today()->addDays(7)->toDateString(),
                ])->count(),
                'open' => $this->openTasks()->count(),
            ],
            'attentionTasks' => $this->present($attentionTasks, $today),
            'attentionTotal' => $this->needsAttention($this->openTasks(), $today)->count(),
            'myTasks' => $this->present($myTasks, $today),
        ]);
    }

    /**
     * Tasks visible to the current user that are not completed yet.
     */
    private function openTasks(): Builder
    {
        return Task::query()
            ->select(['id', 'task_name', 'task_description', 'due_date', 'priority', 'status', 'user_id', 'assign_to'])
            ->with(['assignee:id,name', 'creator:id,name'])
            ->myTasks()
            ->open();
    }

    /**
     * Overdue, due today, or high priority.
     */
    private function needsAttention(Builder $query, string $today): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->where('due_date', '<=', $today)
            ->orWhereIn('priority', self::$HIGH_PRIORITIES)
        );
    }

    /**
     * Shape tasks for the task lists, tagging why each one needs attention.
     *
     * @param  Collection<int, Task>  $tasks
     * @return Collection<int, array<string, mixed>>
     */
    private function present(Collection $tasks, string $today): Collection
    {
        return $tasks->map(function (Task $task) use ($today): array {
            $dueDate = Carbon::parse($task->due_date)->toDateString();

            return [
                ...$task->toListItem(),
                'attention' => match (true) {
                    $dueDate < $today => 'overdue',
                    $dueDate === $today => 'today',
                    default => 'high_priority',
                },
            ];
        })->values();
    }
}
