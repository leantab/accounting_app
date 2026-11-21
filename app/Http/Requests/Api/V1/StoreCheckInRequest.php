<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Project;
use App\Rules\InTenant;
use App\Support\Api\MobileAccess;
use Illuminate\Foundation\Http\FormRequest;

class StoreCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return MobileAccess::recordsCheckIns($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'project_id' => ['nullable', 'integer', new InTenant(Project::class, null, __('api.invalid_project'))],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'latitude.required' => __('check_ins.location_missing'),
            'longitude.required' => __('check_ins.location_missing'),
            'latitude.between' => __('check_ins.location_invalid'),
            'longitude.between' => __('check_ins.location_invalid'),
            'notes.max' => 'Las notas no pueden superar los 500 caracteres.',
        ];
    }
}
