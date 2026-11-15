<?php

namespace App\Http\Resources\V1;

use App\Actions\Rates\ResolveEffectiveUserRate;
use App\Models\DefaultRate;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
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
            'user_role_id' => $this->user_role_id,
            'role' => $this->whenLoaded('role', fn () => $this->role === null ? null : [
                'id' => $this->role->id,
                'name' => $this->role->name,
            ]),
            'name' => $this->name,
            'lastname' => $this->lastname,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'tax_id' => $this->tax_id,
            'is_active' => (bool) $this->is_active,
            'is_admin' => (bool) $this->is_admin,
            'fixed_daily_rate' => $this->fixed_daily_rate,
            'fixed_monthly_rate' => $this->fixed_monthly_rate,
            'individual_rates' => $this->whenLoaded('userRates', fn () => $this->individualRates()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * One line per rated item type: the user's own rate, the customer default and the one that applies.
     *
     * @return list<array<string, mixed>>
     */
    private function individualRates(): array
    {
        $defaultRates = $this->customer_id === null
            ? collect()
            : DefaultRate::query()->where('customer_id', $this->customer_id)->get();

        return TimeTrackerItemType::withRates()
            ->map(function (TimeTrackerItemType $type) use ($defaultRates): array {
                $custom = $this->userRates->firstWhere('time_tracker_item_type_id', $type->id)?->rate;
                $default = $defaultRates->firstWhere('time_tracker_item_type_id', $type->id)?->rate;
                $effective = ResolveEffectiveUserRate::execute($this->userRates, $defaultRates, $type->id);

                return [
                    'time_tracker_item_type_id' => $type->id,
                    'name' => $type->name,
                    'rate' => $custom,
                    'default_rate' => $default,
                    'effective_rate' => $effective === null ? null : number_format($effective, 2, '.', ''),
                ];
            })
            ->all();
    }
}
