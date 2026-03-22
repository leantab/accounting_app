<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCustomer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_id
 * @property int|null $company_id
 * @property string $name
 * @property string|null $description
 * @property string $start_date
 * @property string|null $end_date
 * @property int $project_status_id
 */
class Project extends Model
{
    use BelongsToCustomer;
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'company_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'project_status_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'project_status_id');
    }
}
