<?php

use Illuminate\Support\Facades\Route;

// UI scaffold routes — no auth/policies yet; middleware + policies arrive with the real build.
// Route map follows DEVELOPMENT_PLAN.md §4.

Route::redirect('/', '/requests');

// Static login scaffold — real auth (ExternalAuthService + middleware) arrives with the real build
Route::view('/login', 'login')->name('login');

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

// Print placeholder — the real 3-copy layout ports from print-view.blade.php in the real build
Route::get('/pan/{pan}/print', fn (string $pan) => view('pan-print', ['pan' => $pan]))->name('pan.print');

Route::get('/help/glossary', App\Livewire\Help\Glossary::class)->name('help.glossary');
