<?php

namespace App\Models;

use App\Enums\TimeTrackerItemTypeEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class TimeTrackerItemType extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    public static function findIdFor(TimeTrackerItemTypeEnum $type): ?int
    {
        $id = static::query()
            ->where('name', $type->label())
            ->orderBy('id')
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    public static function ensureIdFor(TimeTrackerItemTypeEnum $type): int
    {
        return static::findIdFor($type) ?? (int) static::query()->create([
            'name' => $type->label(),
        ])->id;
    }

    /**
     * Shows the name of a stored item type in the current language; unknown names are returned as they are.
     */
    public static function translate(string $name): string
    {
        foreach (TimeTrackerItemTypeEnum::cases() as $type) {
            if ($type->label() === $name) {
                return $type->translatedLabel();
            }
        }

        return $name;
    }

    /**
     * @param  list<string>  $names
     */
    public static function translateMany(array $names): string
    {
        return implode(', ', array_map(static::translate(...), $names));
    }

    public function displayName(): string
    {
        return static::translate($this->name);
    }

    /**
     * @return Collection<int, TimeTrackerItemType>
     */
    public static function withRates(): Collection
    {
        foreach (TimeTrackerItemTypeEnum::cases() as $type) {
            static::ensureIdFor($type);
        }

        $excludedNames = collect(TimeTrackerItemTypeEnum::cases())
            ->reject(fn (TimeTrackerItemTypeEnum $type): bool => $type->hasRate())
            ->map(fn (TimeTrackerItemTypeEnum $type): string => $type->label())
            ->all();

        return static::query()
            ->whereNotIn('name', $excludedNames)
            ->orderBy('id')
            ->get()
            ->unique('name')
            ->values();
    }
}
