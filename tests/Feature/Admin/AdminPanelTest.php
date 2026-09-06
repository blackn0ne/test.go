<?php

use App\Enums\UserRole;
use App\Models\District;
use App\Models\Region;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access admin panel', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('admin.login'));
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
    $region = Region::query()->create([
        'name' => 'Туркестанская область',
        'sort_order' => 1,
    ]);
    $district = District::query()->create([
        'region_id' => $region->id,
        'name' => 'Сауран ауданы',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Иванов Иван Иванович',
            'iin' => '123456789012',
            'phone' => '77001234567',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::School->value,
            'region_id' => $region->id,
            'district_id' => $district->id,
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('iin', '123456789012')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::School)
        ->and($user->phone)->toBe('77001234567')
        ->and($user->email)->toBe('77001234567@gotest.kz')
        ->and($user->region_id)->toBe($region->id)
        ->and($user->district_id)->toBe($district->id);
});

test('admin can create users without region and district', function () {
    $admin = User::factory()->admin()->create();
    $school = User::factory()->school()->create([
        'name' => 'СШ №1',
        'iin' => '111111111111',
        'phone' => '77001111111',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Петров Петр Петрович',
            'iin' => '987654321098',
            'phone' => '77009876543',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::User->value,
            'school_id' => $school->id,
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('iin', '987654321098')->first();

    expect($user)->not->toBeNull()
        ->and($user->region_id)->toBeNull()
        ->and($user->district_id)->toBeNull()
        ->and($user->school_id)->toBe($school->id);
});

test('student requires school on create', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Студент Без Школы',
            'iin' => '555555555555',
            'phone' => '77005555555',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::User->value,
        ])
        ->assertSessionHasErrors('school_id');
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

    $this->actingAs($admin)
        ->post(route('admin.directories.regions.store'), [
            'name' => 'Туркестанская область',
            'sort_order' => 1,
        ])
        ->assertRedirect(route('admin.directories.index', ['tab' => 'regions']));

    $region = Region::query()->where('name', 'Туркестанская область')->first();

    expect($region)->not->toBeNull();

    $this->actingAs($admin)
        ->post(route('admin.directories.districts.store'), [
            'name' => 'Сауран ауданы',
            'region_id' => $region->id,
        ])
        ->assertRedirect(route('admin.directories.index', ['tab' => 'districts']));

    $this->actingAs($admin)
        ->get(route('admin.directories.index', ['tab' => 'districts']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('districts', 1)
            ->where('districts.0.name', 'Сауран ауданы')
            ->where('districts.0.region.name', 'Туркестанская область')
        );
});
