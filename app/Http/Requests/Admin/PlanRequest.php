<?php

namespace App\Http\Requests\Admin;

use App\Models\Plan;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 'role:admin' route middleware
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(Plan::class)->ignore($this->route('plan'))],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:100000'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesToSave(): array
    {
        return [
            ...$this->safe()->except('price'),
            'price_paise' => Money::toPaise($this->validated('price')),
        ];
    }
}
