<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use App\Support\Api\MobileAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class MeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'lastname' => $this->lastname,
            'full_name' => $this->fullName,
            'initials' => $this->initials(),
            'email' => $this->email,
            'phone' => $this->phone,
            'is_admin' => (bool) $this->is_admin,
            'is_active' => (bool) $this->is_active,
            'customer' => $this->customer === null ? null : [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
            ],
            'role_id' => $this->user_role_id,
            'role' => $this->role === null ? null : [
                'id' => $this->role->id,
                'name' => $this->role->name,
            ],
            'abilities' => MobileAccess::abilities($this->resource),
        ];
    }
}
