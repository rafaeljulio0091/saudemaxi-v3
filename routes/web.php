<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'demoAvailable' => app()->environment(['local', 'testing']) && config('healthcare.demo_enabled'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'demoAvailable' => app()->environment(['local', 'testing']) && config('healthcare.demo_enabled'),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Registered before the unrestricted healthcare.php routes below so a
// request to a real tenant subdomain is matched here first; a request on
// any other host falls through to the honest "NotReady" fallback.
Route::domain('{tenant}.'.config('healthcare.tenant_base_domain'))
    ->group(fn () => require __DIR__.'/tenant.php');

require __DIR__.'/healthcare.php';
