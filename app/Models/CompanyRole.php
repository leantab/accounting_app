<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CompanyRole model
 *
 * @property int $id
 * @property string $name
 */
class CompanyRole extends Model
{
    public $timestamps = false;

    protected $guarded = [];
}
