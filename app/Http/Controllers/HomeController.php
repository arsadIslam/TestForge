<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestSeries;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * The public homepage. Every number on it comes from the database (cached for 10 minutes,
 * so a busy homepage doesn't run COUNT queries on every visit) — nothing is made up.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        $stats = Cache::remember('home:stats', 600, fn (): array => [
            'questions' => Question::active()->count(),
            'tests' => Test::published()->count(),
            'students' => User::where('role', UserRole::Student)->count(),
        ]);

        return view('public.home', [
            'stats' => $stats,
            'series' => TestSeries::active()
                ->withCount(['tests' => fn ($q) => $q->published()])
                ->orderBy('price_paise')
                ->get(),
        ]);
    }
}
