<?php

namespace App\Http\Resources\Api\AutoX;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar ? Storage::disk('public')->url($this->avatar) : null,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
        ];
    }
}
