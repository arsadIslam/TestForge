<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlanRequest;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * All-Access passes.
 */
class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()
            ->withCount(['payments as sales_count' => fn (Builder $q) => $q->where('status', PaymentStatus::Paid)])
            ->withSum(['payments as revenue_paise' => fn (Builder $q) => $q->where('status', PaymentStatus::Paid)], 'amount_paise')
            ->orderBy('duration_days')
            ->paginate(15);

        return view('admin.plans.index', ['plans' => $plans]);
    }

    public function create(): View
    {
        return view('admin.plans.create', ['plan' => new Plan(['is_active' => true, 'duration_days' => 30, 'price_paise' => 19900])]);
    }

    public function store(PlanRequest $request): RedirectResponse
    {
        $plan = Plan::create($request->attributesToSave());

        return redirect()->route('admin.plans.index')->with('success', "Pass \"{$plan->name}\" created.");
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.edit', ['plan' => $plan]);
    }

    public function update(PlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($request->attributesToSave());

        return redirect()->route('admin.plans.index')->with('success', "Pass \"{$plan->name}\" updated.");
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->payments()->exists()) {
            return back()->with('error', "\"{$plan->name}\" has been bought, so it can't be deleted. Deactivate it instead.");
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')->with('success', "Pass \"{$plan->name}\" deleted.");
    }
}
