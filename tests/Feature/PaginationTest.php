<?php

use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('long PAN lists paginate at 25 per page and search resets to page 1', function () {
    $user = User::factory()->requestor()->create();
    PanRequest::factory()->count(30)->create([
        'requested_by' => $user->id,
        'action_type' => 'promotion',
    ]);

    $component = Livewire::actingAs($user)->test(App\Livewire\Requestor\Index::class);

    expect($component->viewData('pans'))->toHaveCount(25)
        ->and($component->viewData('pans')->total())->toBe(30)
        ->and($component->viewData('stats')['total'])->toBe(30); // stats count everything, not just the page

    $component->call('nextPage');
    expect($component->viewData('pans'))->toHaveCount(5);

    // Typing a search resets back to the first page
    $component->set('search', 'PAN');
    expect($component->viewData('pans')->currentPage())->toBe(1);
});

test('the per-page selector changes page size and rejects tampered values', function () {
    $user = User::factory()->requestor()->create();
    PanRequest::factory()->count(30)->create([
        'requested_by' => $user->id,
        'action_type' => 'promotion',
    ]);

    $component = Livewire::actingAs($user)->test(App\Livewire\Requestor\Index::class);

    $component->set('perPage', 10);
    expect($component->viewData('pans'))->toHaveCount(10)
        ->and($component->viewData('pans')->lastPage())->toBe(3);

    $component->set('perPage', 50);
    expect($component->viewData('pans'))->toHaveCount(30);

    // A hand-tampered value (not one of the offered options) snaps back to the default
    $component->set('perPage', 9999);
    expect($component->get('perPage'))->toBe(25);
});
