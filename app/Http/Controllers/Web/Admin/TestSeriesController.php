<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestSeriesRequest;
use App\Models\TestSeries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TestSeriesController extends Controller
{
    public function index(): View
    {
        $series = TestSeries::query()
            ->withCount([
                'tests',
                'payments as sales_count' => fn (Builder $q) => $q->where('status', PaymentStatus::Paid),
            ])
            ->withSum(['payments as revenue_paise' => fn (Builder $q) => $q->where('status', PaymentStatus::Paid)], 'amount_paise')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.series.index', ['series' => $series]);
    }

    public function create(): View
    {
        return view('admin.series.create', ['series' => new TestSeries(['is_active' => true, 'price_paise' => 29900])]);
    }

    public function store(TestSeriesRequest $request): RedirectResponse
    {
        $series = TestSeries::create($request->attributesToSave());

        return redirect()->route('admin.series.index')->with('success', "Series \"{$series->name}\" created.");
    }

    public function edit(TestSeries $series): View
    {
        return view('admin.series.edit', ['series' => $series]);
    }

    public function update(TestSeriesRequest $request, TestSeries $series): RedirectResponse
    {
        // A new price only affects NEW orders: payments copy the amount when they are created.
        $series->update($request->attributesToSave());

        return redirect()->route('admin.series.index')->with('success', "Series \"{$series->name}\" updated.");
    }

    public function destroy(TestSeries $series): RedirectResponse
    {
        if ($series->payments()->exists()) {
            return back()->with('error', "\"{$series->name}\" has been bought, so it can't be deleted. Deactivate it instead.");
        }

        if ($series->tests()->withTrashed()->exists()) {
            return back()->with('error', "\"{$series->name}\" still has tests. Move them to another series (or Free) first.");
        }

        $series->delete();

        return redirect()->route('admin.series.index')->with('success', "Series \"{$series->name}\" deleted.");
    }
}
