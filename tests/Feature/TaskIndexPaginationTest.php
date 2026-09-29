<?php

use App\Models\Task;
use App\Models\User;
use Database\Seeders\UserRoleSeeder;

beforeEach(function () {
    $this->seed(UserRoleSeeder::class);
});

test('tasks index uses the default page size when no per_page is given', function () {
    $user = User::factory()->create();
    Task::factory()->count(15)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get('/tasks')
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->where('tasks.per_page', 10)
            ->has('tasks.data', 10)
            ->where('tasks.total', 15)
        );
});

test('tasks index respects a whitelisted per_page value', function () {
    $user = User::factory()->create();
    Task::factory()->count(30)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get('/tasks?per_page=25')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.per_page', 25)
            ->has('tasks.data', 25)
        );
});

test('tasks index falls back to the default page size for a non-whitelisted per_page', function () {
    $user = User::factory()->create();
    Task::factory()->count(15)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get('/tasks?per_page=999')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.per_page', 10)
        );

    $this->actingAs($user)
        ->get('/tasks?per_page=not-a-number')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.per_page', 10)
        );
});

test('tasks index only returns the authenticated users own tasks', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Task::factory()->count(3)->create(['user_id' => $user->id]);
    Task::factory()->count(5)->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->get('/tasks')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.total', 3)
        );
});
