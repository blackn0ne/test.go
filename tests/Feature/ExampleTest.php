<?php

test('returns the login screen at the home page', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/Login'));
});
