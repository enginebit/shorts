<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnalyticsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workspaceId' => 'sometimes|string|exists:projects,id',
            'linkId' => 'sometimes|string|exists:links,id',
            'interval' => 'sometimes|string|in:24h,7d,30d,90d,1y,mtd,qtd,ytd,all',
            'timezone' => 'sometimes|string|max:50',
            'start' => 'sometimes|date_format:Y-m-d\TH:i:s.v\Z',
            'end' => 'sometimes|date_format:Y-m-d\TH:i:s.v\Z',
            'country' => 'sometimes|string|max:2',
            'device' => 'sometimes|string|max:50',
            'browser' => 'sometimes|string|max:50',
            'os' => 'sometimes|string|max:50',
            'referer' => 'sometimes|string|max:255',
            'groupBy' => 'sometimes|string|in:timeseries,countries,cities,referrers,devices,browsers,os',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'workspaceId.exists' => 'The specified workspace does not exist.',
            'linkId.exists' => 'The specified link does not exist.',
            'interval.in' => 'The interval must be one of: 24h, 7d, 30d, 90d, 1y, mtd, qtd, ytd, all.',
            'start.date_format' => 'The start date must be in ISO 8601 format.',
            'end.date_format' => 'The end date must be in ISO 8601 format.',
            'country.max' => 'The country code must be 2 characters.',
            'groupBy.in' => 'The groupBy parameter must be one of: timeseries, countries, cities, referrers, devices, browsers, os.',
        ];
    }
}
