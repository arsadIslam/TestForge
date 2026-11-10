<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TestResource;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TestController extends Controller
{
    /**
     * GET /api/v1/tests — tests the student can take now (same query as the web page).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tests = Test::published()
            ->available()
            ->withAttemptInfoFor($request->user()->id)
            ->latest('published_at')
            ->paginate(15);

        return TestResource::collection($tests); // adds links + meta for pagination
    }
}
