<?php

use App\Livewire\Help\Glossary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('a plain Requestor or Division Head does not see confidentiality tags or the role roster', function () {
    foreach ([User::factory()->requestor()->create(), User::factory()->divisionHead()->create()] as $user) {
        $this->actingAs($user);

        Livewire::test(Glossary::class)
            ->assertDontSee('Confidentiality tags')
            ->assertDontSee('HR Head Preparer');
    }
});

test('a DH Head still sees confidentiality tags and the role roster', function () {
    $this->actingAs(User::factory()->dhHead()->create());

    Livewire::test(Glossary::class)
        ->assertSee('Confidentiality tags')
        ->assertSee('HR Head Preparer');
});

test('HR/Admin roles still see confidentiality tags and the role roster', function () {
    foreach ([
        User::factory()->hrPreparer()->create(),
        User::factory()->hrHead()->create(),
        User::factory()->hrApprover()->create(),
        User::factory()->finalApprover()->create(),
        User::factory()->admin()->create(),
    ] as $user) {
        $this->actingAs($user);

        Livewire::test(Glossary::class)->assertSee('Confidentiality tags');
    }
});
