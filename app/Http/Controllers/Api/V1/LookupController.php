<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TimeTrackerItemTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\ProjectStatus;
use App\Models\TimeTrackerItemType;
use App\Models\UserRole;
use App\Support\Api\ItemTypes;
use Illuminate\Http\JsonResponse;

class LookupController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $typesByLabel = collect(TimeTrackerItemTypeEnum::cases())
            ->keyBy(fn (TimeTrackerItemTypeEnum $type): string => $type->label());

        $itemTypes = TimeTrackerItemType::query()
            ->orderBy('id')
            ->get()
            ->unique('name')
            ->values()
            ->map(function (TimeTrackerItemType $itemType) use ($typesByLabel): array {
                $type = $typesByLabel->get($itemType->name);

                return [
                    'id' => $itemType->id,
                    'name' => $itemType->name,
                    'key' => $type?->name,
                    'has_rate' => $type?->hasRate() ?? true,
                    'is_hourly' => $type?->isHourly() ?? false,
                    'shows_description' => ItemTypes::showsDescription($type),
                    'shows_amount' => ItemTypes::showsAmount($type),
                ];
            });

        return response()->json([
            'data' => [
                'user_roles' => UserRole::query()->orderBy('id')->get(['id', 'name']),
                'project_statuses' => ProjectStatus::query()->orderBy('id')->get(['id', 'name']),
                'time_tracker_item_types' => $itemTypes,
            ],
        ]);
    }
}
