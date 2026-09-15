<?php

test('returns the login screen at the home page', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/Login'));
});

test('returns the login screen at the login route', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/Login'));
});
