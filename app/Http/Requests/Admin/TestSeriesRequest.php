<?php

namespace App\Http\Requests\Admin;

use App\Models\TestSeries;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TestSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 'role:admin' route middleware
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => Str::slug((string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
            'validity_days' => $this->filled('validity_days') ? $this->input('validity_days') : null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $series = $this->route('series');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(TestSeries::class)->ignore($series)],
            'slug' => ['required', 'string', 'max:120', Rule::unique(TestSeries::class)->ignore($series)],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:100000'], // rupees
            'validity_days' => ['nullable', 'integer', 'min:1', 'max:3650'],           // blank = lifetime
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * The columns to save — price converted from rupees to integer paise in ONE place.
     *
     * @return array<string, mixed>
     */
    public function attributesToSave(): array
    {
        return [
            ...$this->safe()->except('price'),
            'price_paise' => Money::toPaise($this->validated('price')),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['slug.unique' => 'A series with a very similar name already exists.'];
    }
}
