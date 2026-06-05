<?php

namespace Modules\Export\App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use HasanHawary\ExportBuilder\ExportBuilder;
use Modules\Export\App\Http\Requests\ExportRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends BaseController
{
    public function __invoke(ExportRequest $request): BinaryFileResponse
    {
        return (new ExportBuilder($this->filters($request)))->response();
    }

    /**
     * Prepare filters from the validated request data.
     * Uses validated() to avoid magic __get() access on the request.
     */
    private function filters(ExportRequest $request): array
    {
        $filter = $request->validated();
        $filter['related_type'] = 'count';

        return $filter;
    }
}
