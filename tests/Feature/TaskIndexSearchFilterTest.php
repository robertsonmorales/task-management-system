<?php

use App\Models\Task;
use App\Models\User;
use Database\Seeders\UserRoleSeeder;

beforeEach(function () {
    $this->seed(UserRoleSeeder::class);
});

test('search matches part of the task name only', function () {
    $user = User::factory()->create();
    Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'task_name' => 'Write weekly report']);
    Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'task_name' => 'Buy groceries', 'task_description' => 'Mention the report']);

    $this->actingAs($user)
        ->get('/tasks?search=report')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.total', 1)
            ->where('tasks.data.0.task_name', 'Write weekly report')
            ->where('filters.search', 'report')
        );
});

test('search only returns the authenticated users own tasks', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'task_name' => 'Weekly report']);
    Task::factory()->create(['user_id' => $otherUser->id, 'assign_to' => $otherUser->id, 'task_name' => 'Monthly report']);

    $this->actingAs($user)
        ->get('/tasks?search=report')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.total', 1)
            ->where('tasks.data.0.task_name', 'Weekly report')
        );
});

test('priority and status filters narrow the results', function () {
    $user = User::factory()->create();
    Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'priority' => 'High', 'status' => 'Pending']);
    Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'priority' => 'High', 'status' => 'Completed']);
    Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'priority' => 'Low', 'status' => 'Pending']);

    $this->actingAs($user)
        ->get('/tasks?priority=High')
        ->assertInertia(fn ($page) => $page->where('tasks.total', 2));

    $this->actingAs($user)
        ->get('/tasks?priority=High&status=Pending')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.total', 1)
            ->where('filters.priority', 'High')
            ->where('filters.status', 'Pending')
        );
});

test('unrecognized filter values are ignored', function () {
    $user = User::factory()->create();
    Task::factory()->count(3)->create(['user_id' => $user->id, 'assign_to' => $user->id]);

    $this->actingAs($user)
        ->get('/tasks?priority=Critical&status=Archived&due=someday')
        ->assertInertia(fn ($page) => $page->where('tasks.total', 3));
});

test('due date filters match the expected ranges', function () {
    $this->travelTo('2026-09-15 10:00:00');

    $user = User::factory()->create();
    Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'task_name' => 'Overdue', 'due_date' => '2026-09-10', 'status' => 'Pending']);
    Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'task_name' => 'Overdue but done', 'due_date' => '2026-09-10', 'status' => 'Completed']);
    Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'task_name' => 'Today', 'due_date' => '2026-09-15', 'status' => 'Pending']);
    Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'task_name' => 'In five days', 'due_date' => '2026-09-20', 'status' => 'Pending']);
    Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'task_name' => 'Next month', 'due_date' => '2026-10-05', 'status' => 'Pending']);

    $this->actingAs($user)
        ->get('/tasks?due=overdue')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.total', 1)
            ->where('tasks.data.0.task_name', 'Overdue')
        );

    $this->actingAs($user)
        ->get('/tasks?due=today')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.total', 1)
            ->where('tasks.data.0.task_name', 'Today')
        );

    $this->actingAs($user)
        ->get('/tasks?due=next_7_days')
        ->assertInertia(fn ($page) => $page->where('tasks.total', 2));

    $this->actingAs($user)
        ->get('/tasks?due=next_month')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.total', 1)
            ->where('tasks.data.0.task_name', 'Next month')
        );
});

test('search and filters can be combined', function () {
    $user = User::factory()->create();
    Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'task_name' => 'Fix login bug', 'priority' => 'Urgent']);
    Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'task_name' => 'Fix typo', 'priority' => 'Low']);

    $this->actingAs($user)
        ->get('/tasks?search=fix&priority=Urgent')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.total', 1)
            ->where('tasks.data.0.task_name', 'Fix login bug')
        );
});
