<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Company;
use App\Rules\InTenant;
use App\Support\Api\MobileAccess;

class ProjectRequest extends TenantRequest
{
    public function authorize(): bool
    {
        return MobileAccess::managesProjects($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => $this->customerRule(),
            'company_id' => ['nullable', 'integer', new InTenant(Company::class, $this->customerId(), __('api.invalid_company'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'project_status_id' => ['required', 'integer', 'exists:project_statuses,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'start_date.required' => __('api.enter_start_date'),
            'end_date.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'project_status_id.required' => __('api.choose_status'),
        ];
    }
}
