<?php

namespace App\Http\Controllers\Web\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('student.purchases', [
            'entitlements' => $user->entitlements()->notExpired()->with('series:id,name,slug')->orderBy('starts_at')->get(),
            'payments' => $user->payments()->with('payable')->latest('id')->paginate(15),
        ]);
    }
}
