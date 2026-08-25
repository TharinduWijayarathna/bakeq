<?php

namespace App\Actions;

use App\Enums\CustomerSource;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateManualCustomer
{
    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     phone?: string|null,
     *     address_line?: string|null,
     *     city?: string|null
     * }  $details
     */
    public function handle(array $details): User
    {
        return User::query()->create([
            'name' => $details['name'],
            'email' => $details['email'],
            'phone' => $details['phone'] ?? null,
            'address_line' => $details['address_line'] ?? null,
            'city' => $details['city'] ?? null,
            'role' => UserRole::Customer,
            'customer_source' => CustomerSource::Manual,
            'password' => Hash::make(Str::password(16)),
        ]);
    }
}
