<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;

class AuthenticatedHome
{
    public static function routeName(User $user): string
    {
        if (! $user->hasVerifiedEmail()) {
            return 'verification.notice';
        }

        return match ($user->role?->name) {
            Role::CLIENTE => 'client.home',
            Role::ADMINISTRADOR => 'admin.home',
            default => abort(403),
        };
    }
}
