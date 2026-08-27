<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

class StaffPermissions
{
    /**
     * Ability => roles that may access it.
     *
     * @var array<string, list<string>>
     */
    private const ABILITIES = [
        'dashboard' => ['admin', 'manager', 'baker', 'decorator', 'cashier'],
        'categories' => ['admin', 'manager'],
        'cakes' => ['admin', 'manager'],
        'inventory' => ['admin', 'manager', 'baker'],
        'recipes' => ['admin', 'manager', 'baker'],
        'orders' => ['admin', 'manager', 'cashier', 'baker', 'decorator'],
        'pos' => ['admin', 'manager', 'cashier'],
        'order-assistant' => ['admin', 'manager', 'cashier'],
        'admin-agent' => ['admin', 'manager'],
        'production' => ['admin', 'manager', 'baker', 'decorator'],
        'waste' => ['admin', 'manager', 'baker'],
        'invoices' => ['admin', 'manager', 'cashier'],
        'designer' => ['admin', 'manager'],
        'testimonials' => ['admin', 'manager'],
        'customers' => ['admin', 'manager', 'cashier'],
        'employees' => ['admin', 'manager'],
        'shifts' => ['admin', 'manager', 'baker', 'decorator', 'cashier'],
        'audit' => ['admin', 'manager'],
        'gallery' => ['admin', 'manager', 'decorator'],
    ];

    public static function allows(?User $user, string $ability): bool
    {
        if ($user === null || ! $user->isStaff()) {
            return false;
        }

        $roles = self::ABILITIES[$ability] ?? [];

        return in_array($user->role->value, $roles, true);
    }

    /**
     * @return list<string>
     */
    public static function abilities(): array
    {
        return array_keys(self::ABILITIES);
    }

    public static function routeAbility(string $routeName): ?string
    {
        return match (true) {
            $routeName === 'admin.dashboard' => 'dashboard',
            str_starts_with($routeName, 'admin.categories') => 'categories',
            str_starts_with($routeName, 'admin.cakes') => 'cakes',
            $routeName === 'admin.inventory' => 'inventory',
            str_starts_with($routeName, 'admin.recipes') => 'recipes',
            str_starts_with($routeName, 'admin.orders') => 'orders',
            $routeName === 'admin.pos' => 'pos',
            $routeName === 'admin.order-assistant' => 'order-assistant',
            $routeName === 'admin.admin-agent' => 'admin-agent',
            $routeName === 'admin.production' => 'production',
            $routeName === 'admin.waste' => 'waste',
            str_starts_with($routeName, 'admin.invoices') => 'invoices',
            $routeName === 'admin.designer' => 'designer',
            $routeName === 'admin.testimonials' => 'testimonials',
            str_starts_with($routeName, 'admin.customers') => 'customers',
            $routeName === 'admin.employees' => 'employees',
            $routeName === 'admin.shifts' => 'shifts',
            $routeName === 'admin.audit' => 'audit',
            str_starts_with($routeName, 'admin.gallery') => 'gallery',
            default => null,
        };
    }

    /**
     * @return list<UserRole>
     */
    public static function assignableRoles(): array
    {
        return UserRole::staffCases();
    }
}
