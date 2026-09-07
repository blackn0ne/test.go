<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('admin can visit the dashboard', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk();
});

test('student is redirected from dashboard to exam lobby', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertRedirect(route('exam.show'));
});
