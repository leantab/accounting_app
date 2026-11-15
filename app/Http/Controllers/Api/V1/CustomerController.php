<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\CustomerRequest;
use App\Http\Resources\V1\CustomerResource;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Support\Api\MobileAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerController extends CrudController
{
    protected function canAccess(User $user): bool
    {
        return MobileAccess::managesCustomers($user);
    }

    protected function query(User $user): Builder
    {
        return Customer::query();
    }

    protected function resourceClass(): string
    {
        return CustomerResource::class;
    }

    protected function searchColumns(): array
    {
        return ['name', 'phone', 'email', 'address', 'tax_id'];
    }

    /**
     * Deleting a customer cascades to most of its data, so the app only removes empty ones.
     */
    protected function guardDelete(Request $request, Model $record): void
    {
        /** @var Customer $record */
        $inUse = $record->users()->exists() || Company::query()->where('customer_id', $record->id)->exists();

        abort_if($inUse, 409, 'No se puede eliminar un cliente que todavía tiene usuarios o empresas.');
    }

    public function store(CustomerRequest $request): JsonResponse
    {
        $customer = Customer::query()->create($request->validated());

        return $this->fresh($request, $customer)->response()->setStatusCode(201);
    }

    public function update(CustomerRequest $request, int $id): JsonResource
    {
        $customer = $this->find($request, $id);
        $customer->update($request->validated());

        return $this->fresh($request, $customer);
    }
}
