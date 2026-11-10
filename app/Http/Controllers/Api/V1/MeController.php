<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResultResource;
use App\Http\Resources\SubjectAccuracyResource;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MeController extends Controller
{
    /**
     * GET /api/v1/me/results — the student's results, newest first, paginated.
     */
    public function results(Request $request): AnonymousResourceCollection
    {
        $results = $request->user()->results()
            ->with(['test', 'attempt:id,attempt_no'])
            ->latest('evaluated_at')
            ->paginate(15);

        return ResultResource::collection($results);
    }

    /**
     * GET /api/v1/me/analytics — subject-wise accuracy (weakest first) + overall summary.
     */
    public function analytics(Request $request, AnalyticsService $analytics): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        return SubjectAccuracyResource::collection($analytics->subjectAccuracy($userId))
            ->additional(['summary' => $analytics->studentSummary($userId)]);
    }
}
