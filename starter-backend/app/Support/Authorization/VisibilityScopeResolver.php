<?php

namespace App\Support\Authorization;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VisibilityScopeResolver
{
    public function apply(
        Builder $builder,
        ?Authenticatable $user,
        string $viewAllPermission,
        string $viewOwnPermission,
        callable $ownScope
    ): void {
        if (! $user) {
            $builder->whereRaw('1 = 0');

            return;
        }

        if ($user->can($viewAllPermission)) {
            return;
        }

        if (! $user->can($viewOwnPermission)) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $ownScope($builder, $user);
    }

    public function canAccess(
        Authenticatable $user,
        ?Model $model,
        string $viewAllPermission,
        string $viewOwnPermission,
        callable $owns
    ): bool {
        if ($user->can($viewAllPermission)) {
            return true;
        }

        if (! $user->can($viewOwnPermission)) {
            return false;
        }

        return ! $model || (bool) $owns($user, $model);
    }
}
