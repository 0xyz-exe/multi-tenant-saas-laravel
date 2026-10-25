<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TenantDashboardController;
use App\Http\Controllers\TenantMemberController;
use App\Http\Controllers\TenantPlanController;
use App\Http\Controllers\TenantProjectController;
use App\Http\Controllers\TenantSwitchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [TenantDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Tenant Switch & Creation
    Route::post('/tenants', [TenantSwitchController::class, 'store'])->name('tenants.store');
    Route::post('/tenants/{tenant}/switch', [TenantSwitchController::class, 'switch'])->name('tenants.switch');

    // Tenant Projects
    Route::get('/projects', [TenantProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [TenantProjectController::class, 'store'])->name('projects.store');
    Route::delete('/projects/{project}', [TenantProjectController::class, 'destroy'])->name('projects.destroy');

    // Tenant Members & Invites
    Route::get('/members', [TenantMemberController::class, 'index'])->name('members.index');
    Route::post('/members/invite', [TenantMemberController::class, 'invite'])->name('members.invite');
    Route::delete('/members/{user}', [TenantMemberController::class, 'remove'])->name('members.remove');

    // Tenant Plans & Status
    Route::get('/plans', [TenantPlanController::class, 'index'])->name('plans.index');
    Route::post('/plans/upgrade', [TenantPlanController::class, 'upgrade'])->name('plans.upgrade');
    Route::post('/plans/status', [TenantPlanController::class, 'toggleStatus'])->name('plans.status');
});

require __DIR__.'/auth.php';
