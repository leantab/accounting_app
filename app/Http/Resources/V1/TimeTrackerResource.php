<?php

namespace App\Http\Resources\V1;

use App\Models\TimeTracker;
use App\Models\TimeTrackerItem;
use App\Support\Api\ItemTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TimeTracker
 */
class TimeTrackerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'date_start' => $this->date_start?->format('Y-m-d'),
            'date_end' => $this->date_end?->format('Y-m-d'),
            'hours' => $this->hours,
            'amount' => $this->amount,
            'approved' => (bool) $this->approved,
            'approved_at' => $this->approved_at?->format('Y-m-d'),
            'billed' => (bool) $this->billed,
            'paid' => (bool) $this->paid,
            'paid_date' => $this->paid_date?->format('Y-m-d'),
            'customer_id' => $this->customer_id,
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'invoice_id' => $this->invoice_id,
            'payment_id' => $this->payment_id,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer === null ? null : [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
            ]),
            'user' => $this->whenLoaded('user', fn () => $this->user === null ? null : [
                'id' => $this->user->id,
                'name' => $this->user->fullName,
            ]),
            'project' => $this->whenLoaded('project', fn () => $this->project === null ? null : [
                'id' => $this->project->id,
                'name' => $this->project->name,
            ]),
            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice === null ? null : [
                'id' => $this->invoice->id,
                'name' => $this->invoice->name,
            ]),
            'payment' => $this->whenLoaded('payment', fn () => $this->payment === null ? null : [
                'id' => $this->payment->id,
                'name' => $this->payment->reference,
            ]),
            'approved_by' => $this->whenLoaded('approvedBy', fn () => $this->approvedBy === null ? null : [
                'id' => $this->approvedBy->id,
                'name' => $this->approvedBy->fullName,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items
                ->sortBy([['date', 'asc'], ['id', 'asc']])
                ->values()
                ->map(fn (TimeTrackerItem $item): array => [
                    'id' => $item->id,
                    'date' => $item->date?->format('Y-m-d'),
                    'time_tracker_item_type_id' => $item->time_tracker_item_type_id,
                    'type_name' => $item->timeTrackerItemType?->name,
                    'type_key' => $item->timeTrackerItemType === null
                        ? null
                        : ItemTypes::enumForName($item->timeTrackerItemType->name)?->name,
                    'hours' => $item->hours,
                    'time_start' => $item->time_start?->format('H:i'),
                    'time_end' => $item->time_end?->format('H:i'),
                    'description' => $item->description,
                    'rate' => $item->rate,
                    'amount' => $item->amount,
                ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
