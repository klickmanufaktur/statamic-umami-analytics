<?php

namespace Klickmanufaktur\StatamicUmamiAnalytics\Http\Requests;

class PageAnalyticsRequest extends AnalyticsRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('path')) {
            return;
        }

        $path = '/'.trim((string) $this->query('path'), '/');

        $this->merge([
            'path' => $path === '/' ? '/' : rtrim($path, '/'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'path' => ['required', 'string', 'max:2048'],
        ];
    }

    public function path(): string
    {
        return (string) $this->validated('path');
    }
}
