<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Log, Session;

class TaskController extends Controller
{
    protected $task;

    protected static $PAGE_SIZE = 10;

    protected static $ALLOWED_PAGE_SIZES = [10, 25, 50, 100];

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

        $tasks = $this->task->select([
            'id',
            'task_name',
            'task_description',
            'due_date',
            'priority',
            'status',
            'user_id',
            'created_at',
            'updated_at',
        ])->myTasks()->latest()->paginate($perPage)
            ->withQueryString()
            ->through(function ($item) {
                $item['due_date'] = Carbon::parse($item['due_date'])->format('m/d/Y');

                return $item;
            });

        return inertia('tasks/Index', [
            'tasks' => $tasks,
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
                'priority' => 'required|in:Low,Normal,High,Urgent'
            ]);

            $this->task->create([
                'task_name' => sanitizer($request->input('task_name')),
                'task_description' => $request->input('task_name'),
                'due_date' => sanitizer($request->input('due_date')),
                'priority' => sanitizer($request->input('priority')),
                'user_id' => auth()->id(),
                'status' => 'Pending'
            ]);

            $this->goToLandingPage(true, 'Task added successfully.');

        } catch (\Throwable $th) {
            Log::error($th->getMessage());

            $this->goToLandingPage(false, $th->getMessage());
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
                'status' => 'required|in:Pending,In Progress,Completed'
            ]);

            $this->task->find($id)->update([
                'task_name' => sanitizer($request->input('task_name')),
                'task_description' => $request->input('task_name'),
                'due_date' => sanitizer($request->input('due_date')),
                'priority' => sanitizer($request->input('priority')),
                'user_id' => auth()->id(),
                'status' => sanitizer($request->input('status'))
            ]);

            $this->goToLandingPage(true, 'Task updated successfully.');
        } catch (\Throwable $th) {
            Log::error($th->getMessage());

            $this->goToLandingPage(false, $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->task->find($id)->delete();

            $this->goToLandingPage(true, 'Task deleted successfully.');
        } catch (\Throwable $th) {
            Log::error($th->getMessage());

            $this->goToLandingPage(false, $th->getMessage());
        }
    }

    private function goToLandingPage(bool $success, string $message) {
        Session::flash('success', $success);
        Session::flash('message', $message);

        return to_route('tasks.index');
    }

    public function markAsCompleted(string $id) {
        $this->task->findOrFail($id)->update([
            'status' => 'Completed'
        ]);
    }
}
