<?php

use App\Models\User;
use App\Helpers\Enums\RolesEnum;

if (!function_exists('isAdmin'))
{
    function isAdmin(User $user): bool
    {
        return $user->role->name === \App\Helpers\Enums\RolesEnum::Admin->value;
    }
}
