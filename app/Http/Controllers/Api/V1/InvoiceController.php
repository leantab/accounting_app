<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\InvoiceRequest;
use App\Http\Resources\V1\InvoiceResource;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Api\MobileAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class InvoiceController extends CrudController
{
    protected function canAccess(User $user): bool
    {
        return MobileAccess::managesCustomer($user);
    }

    protected function query(User $user): Builder
    {
        return Invoice::query()->with(['customer', 'fromCompany', 'toCompany', 'items']);
    }

    protected function resourceClass(): string
    {
        return InvoiceResource::class;
    }

    protected function searchColumns(): array
    {
        return ['name', 'invoice_number'];
    }

    protected function filterRules(User $user): array
    {
        return [
            'payed' => ['nullable', 'boolean'],
            ...($user->is_admin ? ['customer_id' => ['nullable', 'integer']] : []),
        ];
    }

    protected function order(Builder $query): void
    {
        $query->orderByDesc('date')->orderByDesc('id');
    }

    public function store(InvoiceRequest $request): JsonResponse
    {
        $invoice = $this->save(null, $request);

        return $this->fresh($request, $invoice)->response()->setStatusCode(201);
    }

    public function update(InvoiceRequest $request, int $id): JsonResource
    {
        /** @var Invoice $invoice */
        $invoice = $this->find($request, $id);

        return $this->fresh($request, $this->save($invoice, $request));
    }

    /**
     * Saves the invoice and replaces its items with the ones sent.
     */
    private function save(?Invoice $invoice, InvoiceRequest $request): Invoice
    {
        return DB::transaction(function () use ($invoice, $request): Invoice {
            if ($invoice === null) {
                $invoice = Invoice::query()->create($request->invoiceAttributes());
            } else {
                $invoice->update($request->invoiceAttributes());
            }

            $items = $request->items();

            if ($items === null) {
                return $invoice;
            }

            $keptIds = [];

            foreach ($items as $item) {
                $data = [
                    'customer_id' => $invoice->customer_id,
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_percentage' => $item['discount_percentage'],
                    'discount_amount' => $item['discount_amount'],
                    'total_price' => $item['total_price'],
                ];

                $existing = isset($item['id']) ? $invoice->items()->whereKey($item['id'])->first() : null;

                if ($existing === null) {
                    $keptIds[] = $invoice->items()->create($data)->id;

                    continue;
                }

                $existing->update($data);
                $keptIds[] = $existing->id;
            }

            $invoice->items()->whereNotIn('id', $keptIds)->delete();

            return $invoice;
        });
    }
}
