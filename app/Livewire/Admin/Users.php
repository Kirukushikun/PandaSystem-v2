<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('User Accounts — PANDA')]
class Users extends Component
{
    public string $search = '';

    public string $filter = 'all'; // all | heads | hr | admins

    /** Human summary of the five stage booleans, mockup-style ("Requestor · Division Head"). */
    public static function stageSummary(User $user): string
    {
        $stages = array_keys(array_filter([
            'Requestor' => $user->is_requestor,
            'Division Head' => $user->is_division_head,
            'HR Preparer' => $user->is_hr_preparer,
            'HR Approver' => $user->is_hr_approver,
            'Final Approver' => $user->is_final_approver,
        ]));

        return $stages === [] ? '—' : implode(' · ', $stages);
    }

    public function render()
    {
        $users = User::query()
            ->with('farm')
            ->when($this->search !== '', function (Builder $q) {
                $q->where(fn (Builder $q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%")
                    ->orWhere('position', 'like', "%{$this->search}%"));
            })
            ->when($this->filter === 'heads', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('is_division_head', true)->orWhere('is_dh_head', true)))
            ->when($this->filter === 'hr', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('is_hr_preparer', true)->orWhere('is_hr_approver', true)->orWhere('is_hr_head', true)))
            ->when($this->filter === 'admins', fn (Builder $q) => $q->where('is_admin', true))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.users', [
            'users' => $users,
            'stats' => [
                'total' => User::count(),
                'heads' => User::where('is_division_head', true)->orWhere('is_dh_head', true)->count(),
                'preparers' => User::where('is_hr_preparer', true)->count(),
                'admins' => User::where('is_admin', true)->count(),
            ],
        ]);
    }
}
