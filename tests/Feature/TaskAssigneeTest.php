<?php

use App\Models\Task;
use App\Models\User;
use Database\Seeders\UserRoleSeeder;

beforeEach(function () {
    $this->seed(UserRoleSeeder::class);
});

/**
 * @return array<string, string>
 */
function taskPayload(array $overrides = []): array
{
    return array_merge([
        'task_name' => 'Prepare quarterly report',
        'task_description' => 'Collect the numbers.',
        'due_date' => now()->addDay()->toDateString(),
        'priority' => 'Normal',
    ], $overrides);
}

test('admin can assign a new task to another user', function () {
    $admin = User::factory()->create(['user_role_id' => 1]);
    $assignee = User::factory()->create();

    $this->actingAs($admin)->post('/tasks', taskPayload(['assign_to' => $assignee->id]));

    $task = Task::sole();
    expect($task->assign_to)->toBe($assignee->id)
        ->and($task->user_id)->toBe($admin->id);
});

test('admin must select an assignee when creating a task', function () {
    $admin = User::factory()->create(['user_role_id' => 1]);

    $this->actingAs($admin)->post('/tasks', taskPayload())
        ->assertSessionHas('success', false);

    expect(Task::count())->toBe(0);
});

test('regular user is always assigned to their own task', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($user)->post('/tasks', taskPayload(['assign_to' => $otherUser->id]));

    expect(Task::sole()->assign_to)->toBe($user->id);
});

test('admin can reassign a task on update while a regular user cannot', function () {
    $admin = User::factory()->create(['user_role_id' => 1]);
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id]);

    $this->actingAs($admin)
        ->put("/tasks/{$task->id}", taskPayload(['status' => 'Pending', 'assign_to' => $otherUser->id]));

    expect($task->fresh()->assign_to)->toBe($otherUser->id);

    $this->actingAs($user)
        ->put("/tasks/{$task->id}", taskPayload(['status' => 'Pending', 'assign_to' => $otherUser->id]));

    expect($task->fresh()->assign_to)->toBe($user->id);
});

test('admin can search users of any role once three characters are typed', function () {
    $admin = User::factory()->create(['user_role_id' => 1, 'name' => 'Admin Morgan']);
    User::factory()->create(['name' => 'Regular Morgan']);
    User::factory()->create(['name' => 'Someone Else']);

    $this->actingAs($admin)
        ->getJson('/users/search?q=Morg')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.name', 'Admin Morgan')
        ->assertJsonPath('1.name', 'Regular Morgan');

    $this->actingAs($admin)
        ->getJson('/users/search?q=Mo')
        ->assertOk()
        ->assertExactJson([]);
});

test('regular user cannot search users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/users/search?q=Morg')
        ->assertForbidden();
});
