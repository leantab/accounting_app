<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 */
class ProjectStatus extends Model
{
    protected $fillable = [
        'name',
    ];

    public $timestamps = false;

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
