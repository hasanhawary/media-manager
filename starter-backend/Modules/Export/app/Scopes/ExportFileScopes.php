<?php

namespace Modules\Export\App\Scopes;

use App\Support\Authorization\VisibilityScopeResolver;
use Illuminate\Database\Eloquent\Builder;

trait ExportFileScopes
{
    public function scopeRelated(Builder $builder): void
    {
        app(VisibilityScopeResolver::class)->apply(
            $builder,
            auth()->user(),
            'view-all-export-file',
            'view-own-export-file',
            function (Builder $query, $user): void {
                $locationIds = method_exists($user, 'locationIds')
                    ? $user->locationIds()->all()
                    : [];

                $query->where(function (Builder $subQuery) use ($user, $locationIds): void {
                    $subQuery->where('created_by', $user->id);

                    if ($locationIds !== []) {
                        $subQuery->orWhereIn('location_id', $locationIds);
                    }
                });
            }
        );
    }
}
