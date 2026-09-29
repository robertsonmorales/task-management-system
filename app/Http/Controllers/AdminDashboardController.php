<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    protected static int $OVERDUE_LIMIT = 6;

    protected static int $WORKLOAD_LIMIT = 8;

    /**
     * Analytics periods mapped to the Task::due() ranges the Tasks page filters by.
     *
     * @var array<string, string>
     */
    protected static array $PERIODS = [
        'today' => 'today',
        'week' => 'this_week',
        'month' => 'this_month',
    ];

    protected static string $DEFAULT_PERIOD = 'week';

    /**
     * Oversight across all tasks and users. Props are closures so a period change only recomputes the status breakdown.
     */
    public function dashboard(Request $request): Response
    {
        $period = array_key_exists((string) $request->input('period'), self::$PERIODS)
            ? $request->input('period')
            : self::$DEFAULT_PERIOD;

        return inertia('admin/Dashboard', [
            'summary' => fn (): array => $this->summary(),
            'attention' => fn (): array => $this->attention(),
            'period' => $period,
            'statusBreakdown' => fn (): array => $this->statusBreakdown(self::$PERIODS[$period]),
            'overdueTasks' => fn (): Collection => Task::query()
                ->with(['assignee:id,name', 'creator:id,name'])
                ->overdue()
                ->orderByPriorityDesc()
                ->orderBy('due_date')
                ->limit(self::$OVERDUE_LIMIT)
                ->get()
                ->map(fn (Task $task): array => $task->toListItem()),
            'workload' => fn (): Collection => $this->workload(),
            'workloadTotal' => fn (): int => User::count(),
        ]);
    }

    /**
     * @return array{total: int, inProgress: int, overdue: int, completed: int}
     */
    private function summary(): array
    {
        return [
            'total' => Task::count(),
            'inProgress' => Task::status('In Progress')->count(),
            'overdue' => Task::overdue()->count(),
            'completed' => Task::status('Completed')->count(),
        ];
    }

    /**
     * Exceptions that call for the admin to step in.
     *
     * @return array{overdue: int, unassigned: int, highPriorityDueToday: int}
     */
    private function attention(): array
    {
        return [
            'overdue' => Task::overdue()->count(),
            'unassigned' => Task::open()->whereNull('assign_to')->count(),
            'highPriorityDueToday' => Task::open()->due('today')->whereIn('priority', ['High', 'Urgent'])->count(),
        ];
    }

    /**
     * Status of the tasks due within the period. Each task lands in exactly one bucket: open tasks past due count as overdue.
     *
     * @return array{overdue: int, pending: int, inProgress: int, completed: int}
     */
    private function statusBreakdown(string $range): array
    {
        $today = Carbon::today()->toDateString();

        $counts = Task::query()
            ->due($range)
            ->selectRaw("SUM(CASE WHEN status != 'Completed' AND due_date < ? THEN 1 ELSE 0 END) AS overdue", [$today])
            ->selectRaw("SUM(CASE WHEN status = 'Pending' AND due_date >= ? THEN 1 ELSE 0 END) AS pending", [$today])
            ->selectRaw("SUM(CASE WHEN status = 'In Progress' AND due_date >= ? THEN 1 ELSE 0 END) AS in_progress", [$today])
            ->selectRaw("SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed")
            ->toBase()
            ->first();

        return [
            'overdue' => (int) $counts->overdue,
            'pending' => (int) $counts->pending,
            'inProgress' => (int) $counts->in_progress,
            'completed' => (int) $counts->completed,
        ];
    }

    /**
     * The users carrying the most at-risk work: most overdue first, then the heaviest open load.
     *
     * @return Collection<int, array{id: int, name: string, open: int, inProgress: int, overdue: int}>
     */
    private function workload(): Collection
    {
        return User::query()
            ->select(['id', 'name'])
            ->withCount([
                'assignedTasks as open_count' => fn ($query) => $query->open(),
                'assignedTasks as in_progress_count' => fn ($query) => $query->status('In Progress'),
                'assignedTasks as overdue_count' => fn ($query) => $query->overdue(),
            ])
            ->orderByDesc('overdue_count')
            ->orderByDesc('open_count')
            ->orderBy('name')
            ->limit(self::$WORKLOAD_LIMIT)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'open' => $user->open_count,
                'inProgress' => $user->in_progress_count,
                'overdue' => $user->overdue_count,
            ]);
    }
}
