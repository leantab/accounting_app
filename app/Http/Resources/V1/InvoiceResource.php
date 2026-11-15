<?php

namespace App\Http\Resources\V1;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Invoice
 */
class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer === null ? null : [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
            ]),
            'from_company' => $this->whenLoaded('fromCompany', fn () => $this->fromCompany === null ? null : [
                'id' => $this->fromCompany->id,
                'name' => $this->fromCompany->name,
            ]),
            'to_company' => $this->whenLoaded('toCompany', fn () => $this->toCompany === null ? null : [
                'id' => $this->toCompany->id,
                'name' => $this->toCompany->name,
            ]),
            'name' => $this->name,
            'invoice_number' => $this->invoice_number,
            'description' => $this->description,
            'date' => $this->date?->format('Y-m-d'),
            'total_amount' => $this->total_amount,
            'discount_percentage' => $this->discount_percentage,
            'discount_amount' => $this->discount_amount,
            'tax_percentage' => $this->tax_percentage,
            'tax_amount' => $this->tax_amount,
            'final_amount' => $this->final_amount,
            'payed_amount' => $this->payed_amount,
            'payed' => (bool) $this->payed,
            'payment_due_date' => $this->payment_due_date?->format('Y-m-d'),
            'items' => $this->whenLoaded('items', fn () => $this->items
                ->sortBy('id')
                ->values()
                ->map(fn (InvoiceItem $item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_percentage' => $item->discount_percentage,
                    'discount_amount' => $item->discount_amount,
                    'total_price' => $item->total_price,
                ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
