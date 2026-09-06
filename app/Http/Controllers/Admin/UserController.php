<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\District;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('admin/users/Index', [
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'iin', 'phone', 'email', 'role', 'created_at']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('admin/users/Create', [
            'roles' => $this->roleOptions(),
            ...$this->locationOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::query()->create([
            ...$request->safe()->except('password_confirmation'),
            'email_verified_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('admin.users.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/Edit', [
            'user' => $user->only([
                'id',
                'name',
                'iin',
                'phone',
                'email',
                'role',
                'region_id',
                'district_id',
            ]),
            'roles' => $this->roleOptions(),
            ...$this->locationOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->except(['password', 'password_confirmation']);

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $user->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('admin.users.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            abort(403);
        }

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('admin.users.index');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return collect(UserRole::cases())->map(fn (UserRole $role) => [
            'value' => $role->value,
            'label' => match ($role) {
                UserRole::Admin => 'Админ',
                UserRole::School => 'Школа',
                UserRole::User => 'Студент',
            },
        ])->all();
    }

    /**
     * @return array{regions: Collection<int, array{id: int, name: string}>, districts: Collection<int, array{id: int, name: string, region_id: int}>}
     */
    private function locationOptions(): array
    {
        return [
            'regions' => Region::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'districts' => District::query()
                ->orderBy('name')
                ->get(['id', 'name', 'region_id']),
        ];
    }
}
