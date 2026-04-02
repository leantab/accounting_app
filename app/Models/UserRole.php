<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * UserRole model
 *
 * @property int $id
 * @property string $name
 */
class UserRole extends Model
{
    public $timestamps = false;

    protected $guarded = [];
}
