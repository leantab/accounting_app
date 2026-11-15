<?php

namespace App\Http\Resources\V1;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
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
            'company' => $this->whenLoaded('company', fn () => $this->company === null ? null : [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]),
            'status' => $this->whenLoaded('status', fn () => $this->status === null ? null : [
                'id' => $this->status->id,
                'name' => $this->status->name,
            ]),
            'name' => $this->name,
            'description' => $this->description,
            'start_date' => $this->start_date === null ? null : Carbon::parse($this->start_date)->format('Y-m-d'),
            'end_date' => $this->end_date === null ? null : Carbon::parse($this->end_date)->format('Y-m-d'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
