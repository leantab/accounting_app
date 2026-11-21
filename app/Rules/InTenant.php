<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * The referenced record must be visible to the signed-in user and, when given, belong to the target customer.
 */
class InTenant implements ValidationRule
{
    /**
     * @param  class-string<Model>  $model
     */
    public function __construct(
        private readonly string $model,
        private readonly ?int $customerId = null,
        private readonly ?string $message = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $query = $this->model::query()->whereKey($value);

        if ($this->customerId !== null) {
            $query->where('customer_id', $this->customerId);
        }

        if (! $query->exists()) {
            $fail($this->message ?? __('api.invalid_value'));
        }
    }
}
