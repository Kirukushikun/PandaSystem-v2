<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // One gate per permission boolean (5 stages + 3 flags). Names match
        // PanWorkflow's 'by' keys. Route groups check these; per-record rules
        // (ownership, Manila/Tarlac) live in the policies — never in the sidebar.
        Gate::define('requestor', fn (User $u) => $u->is_requestor);
        Gate::define('division_head', fn (User $u) => $u->is_division_head);
        Gate::define('hr_preparer', fn (User $u) => $u->is_hr_preparer);
        Gate::define('hr_approver', fn (User $u) => $u->is_hr_approver);
        Gate::define('final_approver', fn (User $u) => $u->is_final_approver);
        Gate::define('hr_head', fn (User $u) => $u->is_hr_head);
        Gate::define('dh_head', fn (User $u) => $u->is_dh_head);
        Gate::define('admin', fn (User $u) => $u->is_admin);
    }
}
