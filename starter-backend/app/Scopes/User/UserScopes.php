<?php

namespace App\Scopes\User;

use App\Support\Authorization\VisibilityScopeResolver;
use Illuminate\Database\Eloquent\Builder;

trait UserScopes
{
    public function scopeRelated(Builder $builder): void
    {
        app(VisibilityScopeResolver::class)->apply(
            $builder,
            auth()->user(),
            'view-all-user',
            'view-own-user',
            fn (Builder $query, $user) => $query->where('created_by', $user->id)
        );
    }

    public function scopeExcludeLoggedInUser(Builder $query): Builder
    {
        return $query->where('id', '!=', auth()->id());
    }

    public function scopeExcludeRoot(Builder $query): Builder
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('name', '!=', 'root');
        });
    }

    public function scopeWithRole(Builder $query, ?string $role = null): Builder
    {
        return $query->when($role, function ($subQuery) use ($role) {
            $subQuery->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        });
    }
}
