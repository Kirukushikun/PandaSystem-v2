<?php

test('the application redirects home to the requests module', function () {
    $response = $this->get('/');

    $response->assertRedirect('/requests');
});
