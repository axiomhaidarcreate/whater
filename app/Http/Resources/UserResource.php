<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'user_type' => $this->user_type,
            'is_active' => $this->is_active,
            'roles' => $this->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name_ar' => $role->display_name_ar,
                ];
            }),
            'permissions' => $this->hasRole('super_admin') 
                ? ['*'] 
                : $this->roles->flatMap->permissions->pluck('name')->unique()->values(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
