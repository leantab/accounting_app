<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeTrackerItemType extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];
}
