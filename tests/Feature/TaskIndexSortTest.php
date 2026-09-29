<?php

use App\Models\Task;
use App\Models\User;
use Database\Seeders\UserRoleSeeder;

beforeEach(function () {
    $this->seed(UserRoleSeeder::class);
});

/**
 * @return list<mixed>
 */
function sortedValues(User $user, string $query, string $field): array
{
    $tasks = test()->actingAs($user)->get("/tasks?{$query}")->viewData('page')['props']['tasks']['data'];

    return collect($tasks)->pluck($field)->all();
}

test('tasks can be sorted by name in both directions', function () {
    $user = User::factory()->create();

    foreach (['Bravo', 'Charlie', 'Alpha'] as $name) {
        Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'task_name' => $name]);
    }

    expect(sortedValues($user, 'sort=task_name&direction=asc', 'task_name'))->toBe(['Alpha', 'Bravo', 'Charlie'])
        ->and(sortedValues($user, 'sort=task_name&direction=desc', 'task_name'))->toBe(['Charlie', 'Bravo', 'Alpha']);
});

test('tasks can be sorted by due date', function () {
    $user = User::factory()->create();

    foreach (['2026-10-03', '2026-10-01', '2026-10-02'] as $dueDate) {
        Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'due_date' => $dueDate]);
    }

    expect(sortedValues($user, 'sort=due_date&direction=asc', 'due_date'))->toBe(['10/01/2026', '10/02/2026', '10/03/2026']);
});

test('priority and status sort in their logical order', function () {
    $user = User::factory()->create();

    foreach ([['High', 'Completed'], ['Low', 'In Progress'], ['Urgent', 'Pending'], ['Normal', 'Pending']] as [$priority, $status]) {
        Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'priority' => $priority, 'status' => $status]);
    }

    expect(sortedValues($user, 'sort=priority&direction=asc', 'priority'))->toBe(['Low', 'Normal', 'High', 'Urgent'])
        ->and(sortedValues($user, 'sort=priority&direction=desc', 'priority'))->toBe(['Urgent', 'High', 'Normal', 'Low'])
        ->and(sortedValues($user, 'sort=status&direction=asc', 'status'))->toBe(['Pending', 'Pending', 'In Progress', 'Completed']);
});

test('an unknown sort column falls back to the default order', function () {
    $user = User::factory()->create();
    Task::factory()->count(2)->create(['user_id' => $user->id, 'assign_to' => $user->id]);

    $this->actingAs($user)
        ->get('/tasks?sort=password&direction=asc')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.sort', null)
            ->where('filters.direction', null)
        );
});

test('admins see every task and can sort by assignee', function () {
    $admin = User::factory()->admin()->create();
    $zoe = User::factory()->create(['name' => 'Zoe']);
    $adam = User::factory()->create(['name' => 'Adam']);
    Task::factory()->create(['user_id' => $admin->id, 'assign_to' => $zoe->id]);
    Task::factory()->create(['user_id' => $admin->id, 'assign_to' => $adam->id]);

    $this->actingAs($admin)
        ->get('/tasks?sort=assignee&direction=asc')
        ->assertInertia(fn ($page) => $page
            ->where('canSortByAssignee', true)
            ->where('filters.sort', 'assignee')
            ->where('tasks.total', 2)
            ->where('tasks.data.0.assign_to.name', 'Adam')
            ->where('tasks.data.1.assign_to.name', 'Zoe')
        );
});

test('regular users cannot sort by assignee', function () {
    $user = User::factory()->create();
    Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id]);

    $this->actingAs($user)
        ->get('/tasks?sort=assignee&direction=asc')
        ->assertInertia(fn ($page) => $page
            ->where('canSortByAssignee', false)
            ->where('filters.sort', null)
        );
});
