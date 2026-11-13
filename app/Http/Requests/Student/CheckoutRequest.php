<?php

namespace App\Http\Requests\Student;

use App\Models\Plan;
use App\Models\TestSeries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Buy" — only WHAT is bought. The price is never accepted from the browser.
 */
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 'role:student' route middleware
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['series', 'plan'])],
            'id' => ['required', 'integer'],
        ];
    }

    public function item(): TestSeries|Plan
    {
        return $this->validated('type') === 'series'
            ? TestSeries::findOrFail($this->validated('id'))
            : Plan::findOrFail($this->validated('id'));
    }
}
