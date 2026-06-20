<?php

namespace Klickmanufaktur\StatamicUmamiAnalytics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Statamic\Facades\User;

class AnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) User::current()?->can('access cp');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'days' => ['nullable', 'integer', Rule::in($this->periods())],
        ];
    }

    public function days(): int
    {
        return (int) ($this->validated('days') ?: config('statamic-umami-analytics.default_period', 30));
    }

    /**
     * @return list<int>
     */
    protected function periods(): array
    {
        return collect(config('statamic-umami-analytics.periods', [7, 30, 90]))
            ->map(fn (mixed $period): int => (int) $period)
            ->filter(fn (int $period): bool => $period > 0)
            ->values()
            ->all();
    }
}
