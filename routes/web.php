<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

// Public — org-standard external authentication (authentication-implementation-guide.md)
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'postLogin'])->name('login.post');
Route::get('/app-login/{id}', [AuthenticationController::class, 'app_login'])->name('app.login');

// Break-glass admin login — disabled while BYPASS_SECRET is empty (bypass-feature-guide.md)
Route::get('/bypass', [App\Http\Controllers\BypassController::class, 'show']);
Route::post('/bypass', [App\Http\Controllers\BypassController::class, 'authenticate'])->middleware('throttle:5,1');

// Everything else requires a signed-in user. Per-module/per-record authorization
// comes from Gates + policies on every route/action — never sidebar visibility.
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::redirect('/', '/requests');

    Route::get('/requests', App\Livewire\Requestor\Index::class)->name('requests.index');
    Route::get('/requests/create', App\Livewire\Requestor\Form::class)->name('requests.create');
    Route::get('/requests/{pan}', App\Livewire\Requestor\Show::class)->name('requests.show');
    Route::get('/division', App\Livewire\DivisionHead\Queue::class)->name('division.queue');
    Route::get('/division/{pan}', App\Livewire\DivisionHead\Show::class)->name('division.show');
    Route::get('/preparation', App\Livewire\HrPreparation\Queue::class)->name('preparation.queue');
    Route::get('/preparation/{pan}/edit', App\Livewire\HrPreparation\PrepareForm::class)->name('preparation.edit');
    Route::get('/preparation/{pan}', App\Livewire\HrPreparation\Show::class)->name('preparation.show');
    Route::get('/employees', App\Livewire\HrPreparation\Employees::class)->name('employees.index');
    Route::get('/employees/{employee}/pans', App\Livewire\HrPreparation\EmployeeHistory::class)->name('employees.history');
    Route::get('/hr-approval', App\Livewire\HrApprover\Queue::class)->name('hr-approval.queue');
    Route::get('/hr-approval/{pan}', App\Livewire\HrApprover\Show::class)->name('hr-approval.show');
    Route::get('/final-approval', App\Livewire\FinalApprover\Queue::class)->name('final-approval.queue');
    Route::get('/final-approval/{pan}', App\Livewire\FinalApprover\Show::class)->name('final-approval.show');

    Route::get('/admin/users', App\Livewire\Admin\Users::class)->name('admin.users');
    Route::get('/admin/users/{user}', App\Livewire\Admin\UserAccess::class)->name('admin.users.access');
    Route::get('/admin/employees', App\Livewire\Admin\Employees::class)->name('admin.employees');

    // Mockup's Maintenance subtabs are separate routes (per CLAUDE.md UI contract)
    Route::redirect('/maintenance', '/maintenance/logs');
    Route::get('/maintenance/logs', App\Livewire\Maintenance\Logs::class)->name('maintenance.logs');
    Route::get('/maintenance/reference', App\Livewire\Maintenance\ReferenceValues::class)->name('maintenance.reference');
    Route::get('/maintenance/backups', App\Livewire\Maintenance\Backups::class)->name('maintenance.backups');
    Route::get('/maintenance/danger', App\Livewire\Maintenance\DangerZone::class)->name('maintenance.danger');

    // Print placeholder — the real 3-copy layout is being wired in pan-print.blade.php
    Route::get('/pan/{pan}/print', fn (string $pan) => view('pan-print', ['pan' => $pan]))->name('pan.print');

    Route::get('/help/glossary', App\Livewire\Help\Glossary::class)->name('help.glossary');
});
