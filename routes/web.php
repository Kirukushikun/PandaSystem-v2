<?php

use Illuminate\Support\Facades\Route;

// UI scaffold routes — no auth/policies yet; middleware + policies arrive with the real build.
// Route map follows DEVELOPMENT_PLAN.md §4.

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
Route::get('/admin/employees', App\Livewire\Admin\Employees::class)->name('admin.employees');
Route::get('/maintenance', App\Livewire\Maintenance\Index::class)->name('maintenance.index');

Route::get('/help/glossary', App\Livewire\Help\Glossary::class)->name('help.glossary');
