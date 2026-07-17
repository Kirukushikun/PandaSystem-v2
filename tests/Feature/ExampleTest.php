<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a signed-in user landing on home is sent to the requests module', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect('/requests');
});
