<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeaderboardEntryResource;
use App\Models\Test;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaderboardController extends Controller
{
    /**
     * GET /api/v1/tests/{test}/leaderboard — top 50 (cached), plus the caller's own rank.
     */
    public function show(Request $request, Test $test, LeaderboardService $leaderboard): AnonymousResourceCollection
    {
        abort_if($test->status === TestStatus::Draft, 404);

        $me = $leaderboard->rankOf($test, $request->user()->id);

        return LeaderboardEntryResource::collection($leaderboard->top($test))->additional([
            'test' => ['id' => $test->id, 'title' => $test->title],
            'me' => $me ? new LeaderboardEntryResource($me) : null,
        ]);
    }
}
