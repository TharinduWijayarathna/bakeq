<?php

namespace App\Enums;

enum ReportType: string
{
    case ProfitLoss = 'profit-loss';
    case Sales = 'sales';
    case Ingredients = 'ingredients';
    case Inventory = 'inventory';
    case Waste = 'waste';
    case Orders = 'orders';
    case Customers = 'customers';
    case Categories = 'categories';
    case Production = 'production';
    case Shifts = 'shifts';

    public function label(): string
    {
        return match ($this) {
            self::ProfitLoss => 'Profit & loss',
            self::Sales => 'Cake sales',
            self::Ingredients => 'Ingredient usage',
            self::Inventory => 'Inventory snapshot',
            self::Waste => 'Waste & losses',
            self::Orders => 'Orders ledger',
            self::Customers => 'Customer spend',
            self::Categories => 'Category revenue',
            self::Production => 'Production board',
            self::Shifts => 'Staff shifts',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ProfitLoss => 'Revenue, COGS, waste, and real earnings for the month.',
            self::Sales => 'Cakes sold, quantities, and revenue by product.',
            self::Ingredients => 'Ingredients consumed from recipes and their cost.',
            self::Inventory => 'Current stock levels, values, and low-stock alerts.',
            self::Waste => 'Spoilage and loss entries with cost impact.',
            self::Orders => 'Every non-cancelled order with payment status.',
            self::Customers => 'Top customers by spend this period.',
            self::Categories => 'Revenue split across cake categories.',
            self::Production => 'Orders by production stage.',
            self::Shifts => 'Scheduled and clocked staff shifts.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ProfitLoss => 'banknote',
            self::Sales => 'cake',
            self::Ingredients => 'layers',
            self::Inventory => 'package',
            self::Waste => 'trash',
            self::Orders => 'clipboard',
            self::Customers => 'users',
            self::Categories => 'tag',
            self::Production => 'layout',
            self::Shifts => 'settings',
        };
    }

    /**
     * @return list<self>
     */
    public static function catalog(): array
    {
        return self::cases();
    }
}
