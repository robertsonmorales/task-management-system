<?php

use App\Models\Task;
use App\Models\User;
use Database\Seeders\UserRoleSeeder;

beforeEach(function () {
    $this->seed(UserRoleSeeder::class);
});

test('assignee can mark their own task as completed', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['assign_to' => $user->id, 'status' => 'Pending']);

    $this->actingAs($user)
        ->patch("/tasks/{$task->id}/complete")
        ->assertSessionHas('success', true);

    expect($task->fresh()->status)->toBe('Completed');
});

test('user cannot mark another user\'s task as completed', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $task = Task::factory()->create(['assign_to' => $otherUser->id, 'status' => 'Pending']);

    $this->actingAs($user)
        ->patch("/tasks/{$task->id}/complete")
        ->assertNotFound();

    expect($task->fresh()->status)->toBe('Pending');
});
