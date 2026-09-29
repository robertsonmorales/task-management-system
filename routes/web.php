<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (! auth()) {
        return Inertia::render('Welcome');
    }

    return to_route('dashboard');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::get('/users/search', [UserController::class, 'search'])->name('users.search');

    Route::resource('/tasks', TaskController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
