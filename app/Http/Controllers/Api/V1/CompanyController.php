<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\CompanyRequest;
use App\Http\Resources\V1\CompanyResource;
use App\Models\Company;
use App\Models\User;
use App\Support\Api\MobileAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyController extends CrudController
{
    protected function canAccess(User $user): bool
    {
        return MobileAccess::managesCustomer($user);
    }

    protected function query(User $user): Builder
    {
        return Company::query()->with('customer');
    }

    protected function resourceClass(): string
    {
        return CompanyResource::class;
    }

    protected function searchColumns(): array
    {
        return ['name', 'phone', 'email', 'address', 'tax_id'];
    }

    protected function filterRules(User $user): array
    {
        return $user->is_admin ? ['customer_id' => ['nullable', 'integer']] : [];
    }

    public function store(CompanyRequest $request): JsonResponse
    {
        $company = Company::query()->create($request->validated());

        return $this->fresh($request, $company)->response()->setStatusCode(201);
    }

    public function update(CompanyRequest $request, int $id): JsonResource
    {
        $company = $this->find($request, $id);
        $company->update($request->validated());

        return $this->fresh($request, $company);
    }
}
