<?php

namespace App\Http\Responses;

use App\Enums\UserRole;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        $home = match ($request->user()?->role) {
            UserRole::User => route('exam.show'),
            default => route('dashboard'),
        };

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended($home);
    }
}
