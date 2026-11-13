<?php

namespace App\Http\Controllers\Web\Student;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Test;
use App\Models\TestSeries;
use App\Services\AccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The catalogue: paid test series and All-Access passes.
 */
class SeriesController extends Controller
{
    public function index(Request $request, AccessService $access): View
    {
        return view('student.series.index', [
            'series' => TestSeries::active()
                ->withCount(['tests' => fn ($q) => $q->published()])
                ->orderBy('price_paise')
                ->get(),
            'plans' => Plan::active()->orderBy('duration_days')->get(),
            'access' => $access->accessFor($request->user()), // one query for the whole page
        ]);
    }

    public function show(Request $request, TestSeries $series, AccessService $access): View
    {
        abort_unless($series->is_active || $access->accessFor($request->user())->ownsSeries($series->id), 404);

        return view('student.series.show', [
            'series' => $series,
            'tests' => Test::published()
                ->where('test_series_id', $series->id)
                ->withAttemptInfoFor($request->user()->id)
                ->with('series:id,name,slug,price_paise')
                ->latest('published_at')
                ->get(),
            'access' => $access->accessFor($request->user()),
            'plans' => Plan::active()->orderBy('duration_days')->get(),
        ]);
    }
}
