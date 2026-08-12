<?php

namespace App\Livewire\Admin;

use App\Models\Department;
use App\Models\Farm;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * One user's stage permissions, department assignments, flags, and profile.
 * The eight booleans map 1:1 onto the users columns; departments are the two
 * pivots. Changes only persist on Save.
 */
#[Layout('layouts.app')]
#[Title('User Access — PANDA')]
class UserAccess extends Component
{
    use WithFileUploads;

    public User $account;

    /** Toggle state for the eight permission switches (persisted on Save). */
    public array $perms = [];

    public array $requestsFor = []; // department ids

    public array $heads = []; // department ids

    public ?int $farmId = null;

    public string $position = '';

    public $esign = null; // Livewire temporary upload

    public const PERM_COLUMNS = [
        'requestor' => 'is_requestor',
        'division_head' => 'is_division_head',
        'hr_preparer' => 'is_hr_preparer',
        'hr_approver' => 'is_hr_approver',
        'final_approver' => 'is_final_approver',
        'hr_head' => 'is_hr_head',
        'dh_head' => 'is_dh_head',
        'admin' => 'is_admin',
        'proxy_approver' => 'is_proxy_approver',
    ];

    public function mount(string $user): void
    {
        $this->account = User::where('username', $user)->firstOrFail();

        foreach (self::PERM_COLUMNS as $key => $column) {
            $this->perms[$key] = (bool) $this->account->{$column};
        }
        $this->requestsFor = $this->account->requestorDepartments()->pluck('departments.id')->all();
        $this->heads = $this->account->headedDepartments()->pluck('departments.id')->all();
        $this->farmId = $this->account->farm_id;
        $this->position = (string) $this->account->position;
    }

    public function togglePerm(string $key): void
    {
        if (! array_key_exists($key, $this->perms)) {
            return;
        }

        // Guard rail: an admin cannot lock themself out of Administration.
        if ($key === 'admin' && $this->account->id === auth()->id() && $this->perms['admin']) {
            $this->js("showToast('You cannot remove your own Admin flag.')");

            return;
        }

        $this->perms[$key] = ! $this->perms[$key];
    }

    public function addDepartment(string $pivot, mixed $departmentId): void
    {
        $departmentId = (int) $departmentId;
        if (! in_array($pivot, ['requestsFor', 'heads'], true) || $departmentId === 0) {
            return;
        }
        if (! in_array($departmentId, $this->{$pivot}, true)) {
            $this->{$pivot}[] = $departmentId;
        }
    }

    public function removeDepartment(string $pivot, int $departmentId): void
    {
        if (! in_array($pivot, ['requestsFor', 'heads'], true)) {
            return;
        }
        $this->{$pivot} = array_values(array_diff($this->{$pivot}, [$departmentId]));
    }

    public function save(): void
    {
        $this->validate([
            'farmId' => 'required|exists:farms,id',
            'position' => 'required|string|max:120',
            'esign' => 'nullable|file|mimes:png|max:2048',
            'requestsFor.*' => 'exists:departments,id',
            'heads.*' => 'exists:departments,id',
        ], [], ['farmId' => 'farm / site']);

        $columns = [];
        foreach (self::PERM_COLUMNS as $key => $column) {
            $columns[$column] = $this->perms[$key];
        }

        $this->account->update([
            ...$columns,
            'farm_id' => $this->farmId,
            'position' => $this->position,
        ]);
        $this->account->requestorDepartments()->sync($this->requestsFor);
        $this->account->headedDepartments()->sync($this->heads);

        if ($this->esign) {
            // private disk — served only through the authenticated esign route
            $path = $this->esign->storeAs('esigns', $this->account->username.'.png');
            $this->account->update(['esign_path' => $path]);
            $this->esign = null;
        }

        $this->js("showToast('Access changes for {$this->account->name} saved.')");
    }

    public function render()
    {
        return view('livewire.admin.user-access', [
            'departments' => Department::orderBy('name')->get(),
            'farms' => Farm::orderBy('name')->get(),
        ]);
    }
}
