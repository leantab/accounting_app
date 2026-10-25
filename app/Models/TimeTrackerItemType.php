<?php

namespace App\Models;

use App\Enums\TimeTrackerItemTypeEnum;
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
}
