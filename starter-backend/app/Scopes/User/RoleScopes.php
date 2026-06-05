<?php

namespace App\Scopes\User;

use App\Support\Authorization\VisibilityScopeResolver;
use Illuminate\Database\Eloquent\Builder;

trait RoleScopes
{
    public function scopeRelated(Builder $builder): void
    {
        app(VisibilityScopeResolver::class)->apply(
            $builder,
            auth()->user(),
            'view-all-role',
            'view-own-role',
            fn (Builder $query, $user) => $query->where('created_by', $user->id)
        );

        $builder->excludeRoot()
            ->excludeLoggedInRole();
    }

    public function scopeExcludeRoot(Builder $query): Builder
    {
        return $query->where('name', '!=', 'root');
    }

    public function scopeExcludeLoggedInRole(Builder $query): Builder
    {
        return $query->whereNotIn('id', auth()->user()->roles()->pluck('id')->toArray());
    }
}
