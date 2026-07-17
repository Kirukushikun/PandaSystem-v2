<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('User Access — PANDA')]
class UserAccess extends Component
{
    public string $user;

    /**
     * Live toggle state for the permission switches. Scaffold only — mirrors the
     * eight fixed boolean columns planned for `users` (no spatie/laravel-permission).
     * Static sample: K. Reyes (Requestor + Division Head on).
     */
    public array $perms = [
        'requestor'      => true,
        'division_head'  => true,
        'hr_preparer'    => false,
        'hr_approver'    => false,
        'final_approver' => false,
        'hr_head'        => false,
        'dh_head'        => false,
        'admin'          => false,
    ];

    public function mount(string $user): void
    {
        $this->user = $user;
    }

    public function togglePerm(string $key): void
    {
        if (array_key_exists($key, $this->perms)) {
            $this->perms[$key] = ! $this->perms[$key];
        }
    }

    public function save(): void
    {
        $this->js("showToast('Access changes saved (UI scaffold — nothing is persisted yet).')");
    }

    public function render()
    {
        return view('livewire.admin.user-access');
    }
}
