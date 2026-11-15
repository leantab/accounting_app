<?php

namespace App\Http\Resources\V1;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
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
            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice === null ? null : [
                'id' => $this->invoice->id,
                'name' => $this->invoice->name,
            ]),
            'reference' => $this->reference,
            'amount' => $this->amount === null ? null : number_format((float) $this->amount, 2, '.', ''),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
