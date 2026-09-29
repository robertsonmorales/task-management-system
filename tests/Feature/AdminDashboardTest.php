<?php

use App\Models\Task;
use App\Models\User;
use Database\Seeders\UserRoleSeeder;

beforeEach(function () {
    $this->seed(UserRoleSeeder::class);
    $this->travelTo('2026-09-16 10:00:00');
});

test('regular users cannot open the admin dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin-dashboard')
        ->assertForbidden();
});

test('admins are sent from the user dashboard to the admin dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/dashboard')
        ->assertRedirect(route('admin-dashboard'));
});

test('admin dashboard summarizes work and surfaces exceptions across all users', function () {
    $admin = User::factory()->admin()->create();
    $alex = User::factory()->create(['name' => 'Alex']);
    $maria = User::factory()->create(['name' => 'Maria']);

    Task::factory()->create(['assign_to' => $alex->id, 'status' => 'Pending', 'priority' => 'Low', 'due_date' => '2026-09-10']);
    Task::factory()->create(['assign_to' => $alex->id, 'status' => 'In Progress', 'priority' => 'Urgent', 'due_date' => '2026-09-14', 'task_name' => 'Urgent overdue']);
    Task::factory()->create(['assign_to' => $maria->id, 'status' => 'In Progress', 'priority' => 'High', 'due_date' => '2026-09-16']);
    Task::factory()->create(['assign_to' => $maria->id, 'status' => 'Completed', 'priority' => 'Normal', 'due_date' => '2026-09-15']);
    Task::factory()->create(['assign_to' => null, 'status' => 'Pending', 'priority' => 'Normal', 'due_date' => '2026-09-30']);

    $this->actingAs($admin)
        ->get('/admin-dashboard')
        ->assertInertia(fn ($page) => $page
            ->component('admin/Dashboard')
            ->where('summary', ['total' => 5, 'inProgress' => 2, 'overdue' => 2, 'completed' => 1])
            ->where('attention', ['overdue' => 2, 'unassigned' => 1, 'highPriorityDueToday' => 1])
            ->where('period', 'week')
            ->where('statusBreakdown', ['overdue' => 1, 'pending' => 0, 'inProgress' => 1, 'completed' => 1])
            ->has('overdueTasks', 2)
            ->where('overdueTasks.0.task_name', 'Urgent overdue')
            ->where('overdueTasks.0.assign_to.name', 'Alex')
            ->where('workload.0', ['id' => $alex->id, 'name' => 'Alex', 'open' => 2, 'inProgress' => 1, 'overdue' => 2])
            ->where('workload.1', ['id' => $maria->id, 'name' => 'Maria', 'open' => 1, 'inProgress' => 1, 'overdue' => 0])
        );
});

test('status breakdown follows the selected period and ignores unknown periods', function () {
    $admin = User::factory()->admin()->create();
    Task::factory()->create(['status' => 'Pending', 'due_date' => '2026-09-16']);
    Task::factory()->create(['status' => 'Pending', 'due_date' => '2026-09-28']);

    $this->actingAs($admin)
        ->get('/admin-dashboard?period=today')
        ->assertInertia(fn ($page) => $page
            ->where('period', 'today')
            ->where('statusBreakdown.pending', 1)
        );

    $this->actingAs($admin)
        ->get('/admin-dashboard?period=month')
        ->assertInertia(fn ($page) => $page->where('statusBreakdown.pending', 2));

    $this->actingAs($admin)
        ->get('/admin-dashboard?period=decade')
        ->assertInertia(fn ($page) => $page->where('period', 'week'));
});

test('admins can filter the task list by assignee or unassigned', function () {
    $admin = User::factory()->admin()->create();
    $alex = User::factory()->create(['name' => 'Alex']);
    Task::factory()->create(['assign_to' => $alex->id]);
    Task::factory()->create(['assign_to' => null]);
    Task::factory()->create();

    $this->actingAs($admin)
        ->get("/tasks?assignee={$alex->id}")
        ->assertInertia(fn ($page) => $page
            ->where('tasks.total', 1)
            ->where('filters.assignee', ['id' => (string) $alex->id, 'name' => 'Alex'])
        );

    $this->actingAs($admin)
        ->get('/tasks?assignee=unassigned')
        ->assertInertia(fn ($page) => $page->where('tasks.total', 1));
});

test('regular users cannot use the assignee filter', function () {
    $user = User::factory()->create();
    Task::factory()->create(['assign_to' => $user->id]);

    $this->actingAs($user)
        ->get('/tasks?assignee=unassigned')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.total', 1)
            ->where('filters.assignee', null)
        );
});
