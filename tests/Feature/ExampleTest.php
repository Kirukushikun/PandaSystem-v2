<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('home sends each account to its own module — role-less accounts get the glossary', function () {
    $this->actingAs(User::factory()->requestor()->create())
        ->get('/')
        ->assertRedirect('/requests');

    auth()->logout();

    $this->actingAs(User::factory()->create()) // all 8 booleans false
        ->get('/')
        ->assertRedirect(route('help.glossary'));
});
