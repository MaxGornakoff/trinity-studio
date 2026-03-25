<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePortfolioProjectRequest extends FormRequest
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
        $allowedProperties = config('portfolio.project_properties', []);

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('portfolio_projects', 'slug')->ignore($this->route('portfolio_project')),
            ],
            'summary' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'thumbnail_url' => ['nullable', 'url', 'max:2048'],
            'desktop_mockup_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
            'mobile_mockup_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
            'logo_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp,svg', 'max:5120'],
            'project_url' => ['nullable', 'url', 'max:2048'],
            'order_column' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
            'show_in_slider' => ['sometimes', 'boolean'],
            'region' => ['required', 'in:russia,world'],
            'map_land_id' => ['nullable', 'string', 'max:100', 'regex:/^land_\d+$/'],
            'properties' => ['nullable', 'array'],
            'properties.*' => ['string', 'in:' . implode(',', $allowedProperties)],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
