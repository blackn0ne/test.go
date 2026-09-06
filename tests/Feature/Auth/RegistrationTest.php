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

test('password reset routes are not available', function () {
    $this->get('/forgot-password')->assertNotFound();
    $this->get('/reset-password/invalid-token')->assertNotFound();

    $this->post('/forgot-password', [
        'email' => 'test@example.com',
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

test('admin can authenticate via admin login', function () {
    $this->seed(AdminSeeder::class);

    $response = $this->post(route('admin.login.store'), [
        'email' => 'mokhamediyar@gmail.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('admin.users.index', absolute: false));
});

test('non admin users can not authenticate via admin login', function () {
    $user = User::factory()->create();

    $response = $this->post(route('admin.login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('admin login screen can be rendered', function () {
    $this->get(route('admin.login'))->assertOk();
});

test('user factory supports admin and school roles', function () {
    $admin = User::factory()->admin()->create();
    $school = User::factory()->school()->create();
    $student = User::factory()->create();

    expect($admin->role)->toBe(UserRole::Admin)
        ->and($school->role)->toBe(UserRole::School)
        ->and($student->role)->toBe(UserRole::User);
});
