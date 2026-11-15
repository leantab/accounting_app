<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Rules\InTenant;
use App\Support\Api\ItemTypes;
use App\Support\Api\MobileAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TimeTrackerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return MobileAccess::usesTimeTrackers($this->user());
    }

    /**
     * The customer the tracker belongs to: chosen by platform admins, fixed for everyone else.
     */
    public function customerId(): ?int
    {
        /** @var User $user */
        $user = $this->user();

        if (! $user->is_admin) {
            return $user->customer_id;
        }

        return $this->filled('customer_id') ? (int) $this->input('customer_id') : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();
        $customerId = $this->customerId();
        $isEmployee = MobileAccess::isEmployee($user);

        return [
            'customer_id' => $user->is_admin ? ['required', 'integer', 'exists:customers,id'] : ['exclude'],
            'user_id' => $isEmployee
                ? ['exclude']
                : ['required', 'integer', new InTenant(User::class, $customerId, 'El usuario seleccionado no es válido.')],
            'project_id' => ['nullable', 'integer', new InTenant(Project::class, $customerId, 'El proyecto seleccionado no es válido.')],
            'name' => ['required', 'string', 'max:255'],
            'date_start' => ['required', 'date_format:Y-m-d'],
            'date_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_start'],
            'description' => ['nullable', 'string'],
            'billed' => $isEmployee ? ['exclude'] : ['sometimes', 'boolean'],
            'invoice_id' => $isEmployee
                ? ['exclude']
                : ['nullable', 'integer', new InTenant(Invoice::class, $customerId, 'La factura seleccionada no es válida.')],
            'paid' => $isEmployee ? ['exclude'] : ['sometimes', 'boolean'],
            'payment_id' => $isEmployee
                ? ['exclude']
                : ['nullable', 'integer', new InTenant(Payment::class, $customerId, 'El pago seleccionado no es válido.')],
            'items' => ['sometimes', 'array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.date' => ['required', 'date_format:Y-m-d'],
            'items.*.time_tracker_item_type_id' => ['required', 'integer', 'exists:time_tracker_item_types,id'],
            'items.*.hours' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'items.*.time_start' => ['nullable', 'date_format:H:i'],
            'items.*.time_end' => ['nullable', 'date_format:H:i'],
            'items.*.description' => ['nullable', 'string', 'max:1000'],
            'items.*.amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('items', []) as $index => $item) {
                if (! is_array($item) || ! is_numeric($item['time_tracker_item_type_id'] ?? null)) {
                    continue;
                }

                $type = ItemTypes::enumFor((int) $item['time_tracker_item_type_id']);

                if ($type?->isHourly() && blank($item['hours'] ?? null)) {
                    $validator->errors()->add("items.{$index}.hours", 'Ingresá la cantidad de horas.');
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Elegí un cliente.',
            'user_id.required' => 'Elegí un usuario.',
            'name.required' => 'Ingresá un nombre.',
            'date_start.required' => 'Ingresá la fecha de inicio.',
            'date_end.required' => 'Ingresá la fecha de fin.',
            'date_end.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'items.*.date.required' => 'Ingresá la fecha del ítem.',
            'items.*.time_tracker_item_type_id.required' => 'Elegí el tipo de ítem.',
            'items.*.hours.integer' => 'Las horas deben ser un número entero.',
            'items.*.amount.numeric' => 'El monto debe ser un número.',
            'items.*.amount.min' => 'El monto no puede ser negativo.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function trackerAttributes(): array
    {
        /** @var User $user */
        $user = $this->user();

        $attributes = $this->safe()->except(['items']);

        if (MobileAccess::isEmployee($user)) {
            $attributes['user_id'] = $user->id;
        }

        return $attributes;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public function items(): ?array
    {
        return $this->has('items') ? array_values($this->validated('items', [])) : null;
    }
}
