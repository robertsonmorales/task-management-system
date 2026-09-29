<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Log;
use Session;

class TaskController extends Controller
{
    protected $task;

    protected static $PAGE_SIZE = 10;

    protected static $ALLOWED_PAGE_SIZES = [10, 25, 50, 100];

    protected static $PRIORITIES = ['Low', 'Normal', 'High', 'Urgent'];

    protected static $STATUSES = ['Pending', 'In Progress', 'Completed'];

    protected static $SORTABLE_COLUMNS = ['task_name', 'assignee', 'due_date', 'priority', 'status'];

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = in_array((int) $request->input('per_page'), self::$ALLOWED_PAGE_SIZES, true)
            ? (int) $request->input('per_page')
            : self::$PAGE_SIZE;

        $query = $this->task->select([
            'id',
            'task_name',
            'task_description',
            'due_date',
            'priority',
            'status',
            'user_id',
            'assign_to',
            'created_at',
            'updated_at',
        ])->with(['assignee:id,name', 'creator:id,name'])->myTasks();

        $canSortByAssignee = $request->user()->isAdmin();
        $sort = $this->resolveSort($request, $canSortByAssignee);

        $tasks = $this->sort($this->search($query, $request), $sort)->paginate($perPage)
            ->withQueryString()
            ->through(fn (Task $task) => $task->toListItem());

        return inertia('tasks/Index', [
            'tasks' => $tasks,
            'filters' => [
                'search' => $request->input('search'),
                'priority' => $request->input('priority'),
                'status' => $request->input('status'),
                'due' => $request->input('due'),
                'assignee' => $this->assigneeFilter($request),
                'sort' => $sort['column'],
                'direction' => $sort['direction'],
            ],
            'canSortByAssignee' => $canSortByAssignee,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'task_name' => 'bail|required|max:255|unique:tasks',
                'task_description' => 'nullable|max:5000',
                'due_date' => 'nullable|date',
                'priority' => 'required|in:Low,Normal,High,Urgent',
                'status' => 'nullable|in:Pending,In Progress,Completed',
                'assign_to' => $this->assigneeRules($request),
            ]);

            $this->task->create([
                'task_name' => sanitizer($request->input('task_name')),
                'task_description' => $request->input('task_description'),
                'due_date' => sanitizer($request->input('due_date')),
                'priority' => sanitizer($request->input('priority')),
                'user_id' => auth()->id(),
                'assign_to' => $this->resolveAssigneeId($request),
                'status' => $request->input('status') ?? 'Pending',
            ]);

            return $this->goToLandingPage(true, 'Task added successfully.');

        } catch (\Throwable $th) {
            Log::error($th->getMessage());

            return $this->goToLandingPage(false, $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $request->validate([
                'task_name' => ['bail', 'required', 'max:255', Rule::unique('tasks')->ignore($id)],
                'task_description' => 'nullable|max:5000',
                'due_date' => 'nullable|date',
                'priority' => 'required|in:Low,Normal,High,Urgent',
                'status' => 'required|in:Pending,In Progress,Completed',
                'assign_to' => $this->assigneeRules($request),
            ]);

            $this->task->find($id)->update([
                'task_name' => sanitizer($request->input('task_name')),
                'task_description' => $request->input('task_description'),
                'due_date' => sanitizer($request->input('due_date')),
                'priority' => sanitizer($request->input('priority')),
                'assign_to' => $this->resolveAssigneeId($request),
                'status' => sanitizer($request->input('status')),
                'updated_by' => auth()->id(),
            ]);

            return $this->goToLandingPage(true, 'Task updated successfully.');
        } catch (\Throwable $th) {
            Log::error($th->getMessage());

            return $this->goToLandingPage(false, $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->task->find($id)->delete();

            return $this->goToLandingPage(true, 'Task deleted successfully.');
        } catch (\Throwable $th) {
            Log::error($th->getMessage());

            return $this->goToLandingPage(false, $th->getMessage());
        }
    }

    /**
     * Flash the outcome and return to the page the request came from (the Tasks page or the Dashboard).
     */
    private function goToLandingPage(bool $success, string $message): RedirectResponse
    {
        Session::flash('success', $success);
        Session::flash('message', $message);

        return back();
    }

    /**
     * Validation rules for the assignee. Only admins pick an assignee, so it is required for them only.
     *
     * @return array<int, mixed>
     */
    private function assigneeRules(Request $request): array
    {
        return [Rule::requiredIf(fn () => $request->user()->isAdmin()), 'nullable', 'integer', 'exists:users,id'];
    }

    /**
     * Admins assign the task to the selected user; regular users are always assigned to themselves.
     */
    private function resolveAssigneeId(Request $request): int
    {
        return $request->user()->isAdmin()
            ? (int) $request->input('assign_to')
            : (int) auth()->id();
    }

    /**
     * Mark one of the current user's tasks as completed.
     */
    public function markAsCompleted(string $id): RedirectResponse
    {
        $this->task->myTasks()->findOrFail($id)->update([
            'status' => 'Completed',
            'updated_by' => auth()->id(),
        ]);

        return $this->goToLandingPage(true, 'Task marked as completed.');
    }

    /**
     * Apply the keyword search and the priority, status, and due date filters to the task query.
     * Unrecognized filter values are ignored.
     */
    private function search(Builder $query, Request $request): Builder
    {
        $keyword = trim((string) $request->input('search'));

        if ($keyword !== '') {
            $query->where('task_name', 'like', "%{$keyword}%");
        }

        if (in_array($request->input('priority'), self::$PRIORITIES, true)) {
            $query->priority($request->input('priority'));
        }

        if (in_array($request->input('status'), self::$STATUSES, true)) {
            $query->status($request->input('status'));
        }

        $query->due($request->input('due'));

        if ($request->user()->isAdmin() && $this->isValidAssigneeFilter($request->input('assignee'))) {
            $query->assignedTo($request->input('assignee'));
        }

        return $query;
    }

    /**
     * The active assignee filter with a display name for its chip, or null when not filtering by assignee.
     *
     * @return array{id: string, name: string}|null
     */
    private function assigneeFilter(Request $request): ?array
    {
        $assignee = $request->input('assignee');

        if (! $request->user()->isAdmin() || ! $this->isValidAssigneeFilter($assignee)) {
            return null;
        }

        if ($assignee === 'unassigned') {
            return ['id' => 'unassigned', 'name' => 'Unassigned'];
        }

        $name = User::whereKey($assignee)->value('name');

        return $name ? ['id' => $assignee, 'name' => $name] : null;
    }

    /**
     * The assignee filter accepts "unassigned" or a user id.
     */
    private function isValidAssigneeFilter(mixed $assignee): bool
    {
        return $assignee === 'unassigned' || (is_string($assignee) && ctype_digit($assignee));
    }

    /**
     * Read the requested sort column and direction, dropping anything not allowed.
     * Only admins may sort by assignee.
     *
     * @return array{column: ?string, direction: ?string}
     */
    private function resolveSort(Request $request, bool $canSortByAssignee): array
    {
        $column = $request->input('sort');
        $isAllowed = in_array($column, self::$SORTABLE_COLUMNS, true)
            && ($column !== 'assignee' || $canSortByAssignee);

        if (! $isAllowed) {
            return ['column' => null, 'direction' => null];
        }

        return [
            'column' => $column,
            'direction' => $request->input('direction') === 'desc' ? 'desc' : 'asc',
        ];
    }

    /**
     * Order the task query by the resolved sort, falling back to newest first.
     * Priority and status sort in their logical order rather than alphabetically.
     *
     * @param  array{column: ?string, direction: ?string}  $sort
     */
    private function sort(Builder $query, array $sort): Builder
    {
        $direction = $sort['direction'];

        match ($sort['column']) {
            'task_name', 'due_date' => $query->orderBy($sort['column'], $direction),
            'assignee' => $query->orderBy(User::select('name')->whereColumn('users.id', 'tasks.assign_to'), $direction),
            'priority' => $this->orderByList($query, 'priority', self::$PRIORITIES, $direction),
            'status' => $this->orderByList($query, 'status', self::$STATUSES, $direction),
            default => null,
        };

        return $query->latest()->orderByDesc('id');
    }

    /**
     * Order a column by the position of its value in the given list.
     *
     * @param  list<string>  $values
     */
    private function orderByList(Builder $query, string $column, array $values, string $direction): Builder
    {
        $cases = collect($values)->map(fn (string $value, int $position) => "WHEN ? THEN {$position}")->implode(' ');

        return $query->orderByRaw("CASE {$column} {$cases} END {$direction}", $values);
    }
}
