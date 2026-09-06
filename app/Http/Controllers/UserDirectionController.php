<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserDirectionRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UserDirectionController extends Controller
{
    public function update(UpdateUserDirectionRequest $request): RedirectResponse
    {
        $request->user()->update([
            'direction_id' => $request->integer('direction_id'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Направление сохранено.']);

        return back();
    }
}
