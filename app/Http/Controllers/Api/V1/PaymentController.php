<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\PaymentRequest;
use App\Http\Resources\V1\PaymentResource;
use App\Models\Payment;
use App\Models\User;
use App\Support\Api\MobileAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentController extends CrudController
{
    protected function canAccess(User $user): bool
    {
        return MobileAccess::managesCustomer($user);
    }

    protected function query(User $user): Builder
    {
        return Payment::query()->with(['customer', 'fromCompany', 'toCompany', 'invoice']);
    }

    protected function resourceClass(): string
    {
        return PaymentResource::class;
    }

    protected function searchColumns(): array
    {
        return ['reference'];
    }

    protected function filterRules(User $user): array
    {
        return [
            'invoice_id' => ['nullable', 'integer'],
            ...($user->is_admin ? ['customer_id' => ['nullable', 'integer']] : []),
        ];
    }

    protected function order(Builder $query): void
    {
        $query->orderByDesc('id');
    }

    public function store(PaymentRequest $request): JsonResponse
    {
        $payment = Payment::query()->create($request->validated());

        return $this->fresh($request, $payment)->response()->setStatusCode(201);
    }

    public function update(PaymentRequest $request, int $id): JsonResource
    {
        $payment = $this->find($request, $id);
        $payment->update($request->validated());

        return $this->fresh($request, $payment);
    }
}
