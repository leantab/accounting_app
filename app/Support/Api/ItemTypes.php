<?php

namespace App\Support\Api;

use App\Enums\TimeTrackerItemTypeEnum;
use App\Models\TimeTrackerItemType;

/**
 * Resolves item type rows to their enum case. Rows are matched by name, as the rest of the application does.
 */
final class ItemTypes
{
    public static function enumFor(?int $itemTypeId): ?TimeTrackerItemTypeEnum
    {
        if ($itemTypeId === null) {
            return null;
        }

        $name = TimeTrackerItemType::query()->whereKey($itemTypeId)->value('name');

        return $name === null ? null : self::enumForName($name);
    }

    public static function enumForName(string $name): ?TimeTrackerItemTypeEnum
    {
        foreach (TimeTrackerItemTypeEnum::cases() as $type) {
            if ($type->label() === $name) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Every type accepts an optional description typed by the worker.
     */
    public static function showsDescription(?TimeTrackerItemTypeEnum $type): bool
    {
        return true;
    }

    /**
     * Types whose amount is typed by the worker instead of coming from a rate.
     */
    public static function showsAmount(?TimeTrackerItemTypeEnum $type): bool
    {
        return in_array($type, [TimeTrackerItemTypeEnum::EXPENSES, TimeTrackerItemTypeEnum::TRAVEL], true);
    }
}
