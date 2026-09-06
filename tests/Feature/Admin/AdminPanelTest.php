<?php

use App\Enums\UserRole;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access admin panel', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

test('non-admin users cannot access admin panel', function () {
    $user = User::factory()->school()->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('admin can access users page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('admin can create users', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Иванов Иван Иванович',
            'iin' => '123456789012',
            'phone' => '77001234567',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::School->value,
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('iin', '123456789012')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::School)
        ->and($user->phone)->toBe('77001234567')
        ->and($user->email)->toBe('77001234567@gotest.kz');
});

test('admin can update site settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), [
            'project_name' => 'Moka Test',
            'description' => 'Online testing platform',
            'keywords' => 'tests, school',
            'address' => 'Almaty',
            'phone' => '+7 700 000 0000',
            'social_networks' => [
                'facebook' => 'https://facebook.com/moka',
                'instagram' => '',
                'telegram' => '',
                'youtube' => '',
                'whatsapp' => '',
            ],
        ])
        ->assertRedirect(route('admin.settings.edit'));

    $settings = SiteSetting::current();

    expect($settings->project_name)->toBe('Moka Test')
        ->and($settings->phone)->toBe('+7 700 000 0000');
});

test('admin can manage directories', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.directories.classes.store'), [
            'name' => '10А',
            'sort_order' => 1,
        ])
        ->assertRedirect(route('admin.directories.index', ['tab' => 'classes']));

    $this->actingAs($admin)
        ->post(route('admin.directories.subjects.store'), [
            'name' => 'Математика',
            'school_class_ids' => [1],
        ])
        ->assertRedirect(route('admin.directories.index', ['tab' => 'subjects']));

    $this->actingAs($admin)
        ->post(route('admin.directories.groups.store'), [
            'name' => 'Группа A',
            'subject_id' => 1,
        ])
        ->assertRedirect(route('admin.directories.index', ['tab' => 'groups']));

    $this->actingAs($admin)
        ->get(route('admin.directories.index', ['tab' => 'groups']))
        ->assertOk();
});
