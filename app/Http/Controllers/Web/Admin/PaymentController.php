<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->enum('status', PaymentStatus::class);
        $search = trim((string) $request->query('search'));

        $payments = Payment::query()
            ->with(['user:id,name,email', 'payable'])
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->when($search !== '', fn (Builder $q) => $q->whereHas('user', fn (Builder $u) => $u
                ->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // One GROUP BY for the headline numbers.
        $totals = Payment::query()->toBase()
            ->selectRaw('status, COUNT(*) AS payments, COALESCE(SUM(amount_paise), 0) AS amount_paise')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return view('admin.payments.index', [
            'payments' => $payments,
            'totals' => $totals,
            'statuses' => PaymentStatus::cases(),
            'filters' => ['status' => $status?->value, 'search' => $search],
        ]);
    }
}
