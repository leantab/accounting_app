<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DefaultRatesRequest;
use App\Models\DefaultRate;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use App\Support\Api\MobileAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DefaultRateController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless(MobileAccess::managesCustomer($user), 403);

        $filters = $request->validate(['customer_id' => ['nullable', 'integer', 'exists:customers,id']]);
        $customerId = $user->is_admin ? ($filters['customer_id'] ?? null) : $user->customer_id;

        return response()->json(['data' => $this->payload($customerId === null ? null : (int) $customerId)]);
    }

    public function update(DefaultRatesRequest $request): JsonResponse
    {
        $customerId = (int) $request->customerId();

        /** @var array<int|string, mixed> $rates */
        $rates = $request->validated('rates', []);

        foreach (TimeTrackerItemType::withRates() as $type) {
            if (! array_key_exists($type->id, $rates)) {
                continue;
            }

            if (blank($rates[$type->id])) {
                DefaultRate::query()
                    ->where('customer_id', $customerId)
                    ->where('time_tracker_item_type_id', $type->id)
                    ->delete();

                continue;
            }

            DefaultRate::query()->updateOrCreate(
                ['customer_id' => $customerId, 'time_tracker_item_type_id' => $type->id],
                ['rate' => $rates[$type->id]],
            );
        }

        return response()->json([
            'message' => __('rates.default_rates_saved'),
            'data' => $this->payload($customerId),
        ]);
    }

    /**
     * One line per rated item type, with the rate of the customer when there is one.
     *
     * @return array{customer_id: int|null, rates: list<array{time_tracker_item_type_id: int, name: string, rate: string|null}>}
     */
    private function payload(?int $customerId): array
    {
        $rates = $customerId === null
            ? collect()
            : DefaultRate::query()->where('customer_id', $customerId)->get()->keyBy('time_tracker_item_type_id');

        return [
            'customer_id' => $customerId,
            'rates' => TimeTrackerItemType::withRates()
                ->map(fn (TimeTrackerItemType $type): array => [
                    'time_tracker_item_type_id' => $type->id,
                    'name' => $type->name,
                    'rate' => $rates->get($type->id)?->rate,
                ])
                ->all(),
        ];
    }
}
