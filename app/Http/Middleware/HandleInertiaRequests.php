<?php

namespace App\Http\Middleware;

use App\Models\Direction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            $user->loadMissing([
                'direction:id,code,name',
                'school:id,name',
            ]);
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'iin' => $user->iin,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'direction' => $user->direction?->only(['id', 'code', 'name']),
                    'school' => $user->isSchool()
                        ? $user->only(['id', 'name'])
                        : $user->school?->only(['id', 'name']),
                    'must_select_direction' => $user->mustSelectDirection(),
                ] : null,
            ],
            'directions' => $user && ! $user->isAdmin()
                ? Direction::query()
                    ->with(['subjects:id,name'])
                    ->orderBy('code')
                    ->get(['id', 'code', 'name'])
                    ->map(fn (Direction $direction) => [
                        'id' => $direction->id,
                        'code' => $direction->code,
                        'name' => $direction->name,
                        'subjects' => $direction->subjects->map->only(['id', 'name'])->values(),
                    ])
                    ->values()
                : [],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
