<?php

use App\Models\Task;
use App\Models\User;
use Database\Seeders\UserRoleSeeder;

beforeEach(function () {
    $this->seed(UserRoleSeeder::class);
});

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});

test('dashboard summarizes and prioritizes the users open tasks', function () {
    $this->travelTo('2026-09-15 10:00:00');

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $mine = ['user_id' => $user->id, 'assign_to' => $user->id, 'status' => 'Pending', 'priority' => 'Normal'];

    Task::factory()->create([...$mine, 'task_name' => 'High later', 'due_date' => '2026-09-25', 'priority' => 'High']);
    Task::factory()->create([...$mine, 'task_name' => 'Due today', 'due_date' => '2026-09-15']);
    Task::factory()->create([...$mine, 'task_name' => 'Overdue', 'due_date' => '2026-09-10']);
    Task::factory()->create([...$mine, 'task_name' => 'Upcoming', 'due_date' => '2026-09-18']);
    Task::factory()->create([...$mine, 'task_name' => 'Overdue but done', 'due_date' => '2026-09-10', 'status' => 'Completed']);
    Task::factory()->create(['user_id' => $otherUser->id, 'assign_to' => $otherUser->id, 'due_date' => '2026-09-10', 'status' => 'Pending']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('summary', ['overdue' => 1, 'dueToday' => 1, 'upcoming' => 1, 'open' => 4])
            ->where('attentionTotal', 3)
            ->where('attentionTasks.0.task_name', 'Overdue')
            ->where('attentionTasks.0.attention', 'overdue')
            ->where('attentionTasks.1.task_name', 'Due today')
            ->where('attentionTasks.1.attention', 'today')
            ->where('attentionTasks.2.task_name', 'High later')
            ->where('attentionTasks.2.attention', 'high_priority')
            ->where('attentionTasks.0.created_by.id', $user->id)
            ->has('myTasks', 1)
            ->where('myTasks.0.task_name', 'Upcoming')
        );
});

test('users can mark their own task as completed', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'assign_to' => $user->id, 'status' => 'Pending']);

    $this->actingAs($user)
        ->from('/dashboard')
        ->patch("/tasks/{$task->id}/complete")
        ->assertRedirect('/dashboard');

    expect($task->fresh()->status)->toBe('Completed');
});

test('users cannot complete tasks assigned to someone else', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $otherUser->id, 'assign_to' => $otherUser->id, 'status' => 'Pending']);

    $this->actingAs($user)
        ->patch("/tasks/{$task->id}/complete")
        ->assertNotFound();

    expect($task->fresh()->status)->toBe('Pending');
});
