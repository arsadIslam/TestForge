<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('teacher.dashboard');
    }
}
