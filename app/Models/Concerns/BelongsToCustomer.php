<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToCustomer
{
    protected static function bootBelongsToCustomer(): void
    {
        static::addGlobalScope('customer', function (Builder $builder): void {
            $user = Auth::user();

            if (! $user) {
                return;
            }

            $isAdmin = (bool) $user->is_admin;

            if ($isAdmin) {
                return;
            }

            $customerId = null;

            if (method_exists($user, 'currentCustomerId')) {
                $customerId = $user->currentCustomerId();
            } elseif (isset($user->customer_id)) {
                $customerId = $user->customer_id;
            }

            if ($customerId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $table = $builder->getModel()->getTable();

            $builder->where("{$table}.customer_id", $customerId);
        });

        static::creating(function (Model $model): void {
            $user = Auth::user();

            if (! $user) {
                return;
            }

            $isAdmin = (bool) $user->is_admin;

            if ($isAdmin) {
                return;
            }

            $customerId = null;

            if (method_exists($user, 'currentCustomerId')) {
                $customerId = $user->currentCustomerId();
            } elseif (isset($user->customer_id)) {
                $customerId = $user->customer_id;
            }

            if ($customerId !== null && ! $model->getAttribute('customer_id')) {
                $model->setAttribute('customer_id', $customerId);
            }
        });
    }
}
