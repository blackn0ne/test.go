<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registration routes are not available', function () {
    $this->get('/register')->assertNotFound();

    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});

test('users have a role with user as default', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(UserRole::User);
});

test('admin seeder creates the platform admin', function () {
    $this->seed(AdminSeeder::class);

    $admin = User::query()->where('email', 'mokhamediyar@gmail.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->name)->toBe('Moka Admin')
        ->and($admin->role)->toBe(UserRole::Admin);
});

test('admin can authenticate with seeded credentials', function () {
    $this->seed(AdminSeeder::class);

    $response = $this->post(route('login.store'), [
        'email' => 'mokhamediyar@gmail.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('user factory supports admin and school roles', function () {
    $admin = User::factory()->admin()->create();
    $school = User::factory()->school()->create();
    $student = User::factory()->create();

    expect($admin->role)->toBe(UserRole::Admin)
        ->and($school->role)->toBe(UserRole::School)
        ->and($student->role)->toBe(UserRole::User);
});
