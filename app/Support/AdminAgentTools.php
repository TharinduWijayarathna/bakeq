<?php

namespace App\Support;

use App\Actions\AdjustInventoryForOrder;
use App\Actions\CreateManualCustomer;
use App\Actions\CreateManualOrder;
use App\Actions\CreatePosOrder;
use App\Actions\GenerateInvoice;
use App\Actions\UpdateOrderStatus;
use App\Enums\IngredientUnit;
use App\Enums\OrderStatus;
use App\Enums\ProductionStatus;
use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Enums\WasteReason;
use App\Exceptions\InsufficientStockException;
use App\Models\Cake;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use App\Models\WasteEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminAgentTools
{
    /**
     * Ability required to run each tool.
     *
     * @var array<string, string>
     */
    private const TOOL_ABILITIES = [
        'get_dashboard_summary' => 'dashboard',
        'search_orders' => 'orders',
        'get_order' => 'orders',
        'update_order_status' => 'orders',
        'update_production_status' => 'production',
        'search_cakes' => 'cakes',
        'get_cake' => 'cakes',
        'create_cake' => 'cakes',
        'update_cake' => 'cakes',
        'list_categories' => 'categories',
        'create_category' => 'categories',
        'search_customers' => 'customers',
        'get_customer' => 'customers',
        'create_customer' => 'customers',
        'search_ingredients' => 'inventory',
        'list_low_stock' => 'inventory',
        'create_ingredient' => 'inventory',
        'adjust_ingredient_stock' => 'inventory',
        'log_waste' => 'waste',
        'create_manual_order' => 'orders',
        'create_pos_order' => 'pos',
        'list_employees' => 'employees',
        'list_todays_shifts' => 'shifts',
        'list_who_is_on' => 'shifts',
        'create_shift' => 'shifts',
    ];

    /**
     * AI functionDeclarations payload.
     *
     * @return list<array<string, mixed>>
     */
    public static function declarations(): array
    {
        return [
            self::fn('get_dashboard_summary', 'Month revenue, orders, margin, and budget progress for the bakery dashboard.', [
                'type' => 'OBJECT',
                'properties' => (object) [],
            ]),
            self::fn('search_orders', 'Search recent orders by status, customer name/email/phone, or free text.', [
                'type' => 'OBJECT',
                'properties' => [
                    'query' => ['type' => 'STRING', 'description' => 'Customer name, email, phone, or order notes snippet'],
                    'status' => ['type' => 'STRING', 'description' => 'pending|confirmed|baking|delivered|cancelled'],
                    'limit' => ['type' => 'INTEGER', 'description' => 'Max results (default 8, max 20)'],
                ],
            ]),
            self::fn('get_order', 'Get full details for one order by ID.', [
                'type' => 'OBJECT',
                'properties' => [
                    'order_id' => ['type' => 'INTEGER'],
                ],
                'required' => ['order_id'],
            ]),
            self::fn('update_order_status', 'Change an order status (pending, confirmed, baking, delivered, cancelled). Syncs inventory when needed.', [
                'type' => 'OBJECT',
                'properties' => [
                    'order_id' => ['type' => 'INTEGER'],
                    'status' => ['type' => 'STRING', 'description' => 'pending|confirmed|baking|delivered|cancelled'],
                ],
                'required' => ['order_id', 'status'],
            ]),
            self::fn('update_production_status', 'Move an order on the production board (planning, baking, decorating, qc, ready, delivered).', [
                'type' => 'OBJECT',
                'properties' => [
                    'order_id' => ['type' => 'INTEGER'],
                    'production_status' => ['type' => 'STRING'],
                ],
                'required' => ['order_id', 'production_status'],
            ]),
            self::fn('search_cakes', 'Search catalog cakes by name or description.', [
                'type' => 'OBJECT',
                'properties' => [
                    'query' => ['type' => 'STRING'],
                    'active_only' => ['type' => 'BOOLEAN'],
                    'limit' => ['type' => 'INTEGER'],
                ],
            ]),
            self::fn('get_cake', 'Get one cake by ID.', [
                'type' => 'OBJECT',
                'properties' => [
                    'cake_id' => ['type' => 'INTEGER'],
                ],
                'required' => ['cake_id'],
            ]),
            self::fn('create_cake', 'Create a catalog cake. Price is in Sri Lankan rupees.', [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => ['type' => 'STRING'],
                    'category_id' => ['type' => 'INTEGER'],
                    'price_rupees' => ['type' => 'NUMBER'],
                    'description' => ['type' => 'STRING'],
                    'lead_days' => ['type' => 'INTEGER'],
                    'is_active' => ['type' => 'BOOLEAN'],
                    'is_featured' => ['type' => 'BOOLEAN'],
                ],
                'required' => ['name', 'category_id', 'price_rupees'],
            ]),
            self::fn('update_cake', 'Update cake fields. Price in rupees when provided.', [
                'type' => 'OBJECT',
                'properties' => [
                    'cake_id' => ['type' => 'INTEGER'],
                    'name' => ['type' => 'STRING'],
                    'price_rupees' => ['type' => 'NUMBER'],
                    'description' => ['type' => 'STRING'],
                    'lead_days' => ['type' => 'INTEGER'],
                    'is_active' => ['type' => 'BOOLEAN'],
                    'is_featured' => ['type' => 'BOOLEAN'],
                    'category_id' => ['type' => 'INTEGER'],
                ],
                'required' => ['cake_id'],
            ]),
            self::fn('list_categories', 'List cake categories with cake counts.', [
                'type' => 'OBJECT',
                'properties' => (object) [],
            ]),
            self::fn('create_category', 'Create a cake category.', [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => ['type' => 'STRING'],
                    'sort' => ['type' => 'INTEGER'],
                ],
                'required' => ['name'],
            ]),
            self::fn('search_customers', 'Search customers by name, email, or phone.', [
                'type' => 'OBJECT',
                'properties' => [
                    'query' => ['type' => 'STRING'],
                    'limit' => ['type' => 'INTEGER'],
                ],
                'required' => ['query'],
            ]),
            self::fn('get_customer', 'Get one customer by user ID.', [
                'type' => 'OBJECT',
                'properties' => [
                    'customer_id' => ['type' => 'INTEGER'],
                ],
                'required' => ['customer_id'],
            ]),
            self::fn('create_customer', 'Create a walk-in/manual customer account.', [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => ['type' => 'STRING'],
                    'email' => ['type' => 'STRING'],
                    'phone' => ['type' => 'STRING'],
                    'address_line' => ['type' => 'STRING'],
                    'city' => ['type' => 'STRING'],
                ],
                'required' => ['name', 'email'],
            ]),
            self::fn('search_ingredients', 'Search inventory ingredients by name.', [
                'type' => 'OBJECT',
                'properties' => [
                    'query' => ['type' => 'STRING'],
                    'limit' => ['type' => 'INTEGER'],
                ],
            ]),
            self::fn('list_low_stock', 'List ingredients at or below reorder threshold.', [
                'type' => 'OBJECT',
                'properties' => (object) [],
            ]),
            self::fn('create_ingredient', 'Add an inventory ingredient. unit_cost_rupees is per unit.', [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => ['type' => 'STRING'],
                    'unit' => ['type' => 'STRING', 'description' => 'g|kg|ml|l|pcs|packs'],
                    'current_stock' => ['type' => 'NUMBER'],
                    'unit_cost_rupees' => ['type' => 'NUMBER'],
                    'reorder_threshold' => ['type' => 'NUMBER'],
                    'supplier' => ['type' => 'STRING'],
                ],
                'required' => ['name', 'unit'],
            ]),
            self::fn('adjust_ingredient_stock', 'Set or delta-adjust ingredient stock.', [
                'type' => 'OBJECT',
                'properties' => [
                    'ingredient_id' => ['type' => 'INTEGER'],
                    'set_stock' => ['type' => 'NUMBER', 'description' => 'Absolute stock to set'],
                    'add_stock' => ['type' => 'NUMBER', 'description' => 'Amount to add (negative to subtract)'],
                    'unit_cost_rupees' => ['type' => 'NUMBER'],
                    'reorder_threshold' => ['type' => 'NUMBER'],
                ],
                'required' => ['ingredient_id'],
            ]),
            self::fn('log_waste', 'Log a waste entry for an ingredient or cake and deduct ingredient stock when applicable.', [
                'type' => 'OBJECT',
                'properties' => [
                    'item_type' => ['type' => 'STRING', 'description' => 'ingredient|cake'],
                    'ingredient_id' => ['type' => 'INTEGER'],
                    'cake_id' => ['type' => 'INTEGER'],
                    'quantity' => ['type' => 'NUMBER'],
                    'reason' => ['type' => 'STRING', 'description' => 'spoilage|mistake|sample|other'],
                    'notes' => ['type' => 'STRING'],
                    'wasted_on' => ['type' => 'STRING', 'description' => 'YYYY-MM-DD'],
                ],
                'required' => ['item_type', 'quantity', 'reason'],
            ]),
            self::fn('create_manual_order', 'Create a walk-in catalog order for a customer and cake.', [
                'type' => 'OBJECT',
                'properties' => [
                    'customer_id' => ['type' => 'INTEGER'],
                    'cake_id' => ['type' => 'INTEGER'],
                    'quantity' => ['type' => 'INTEGER'],
                    'delivery_date' => ['type' => 'STRING', 'description' => 'YYYY-MM-DD'],
                    'delivery_address' => ['type' => 'STRING'],
                    'fulfillment_method' => ['type' => 'STRING', 'description' => 'pickup|delivery'],
                    'notes' => ['type' => 'STRING'],
                ],
                'required' => ['customer_id', 'cake_id', 'delivery_date', 'delivery_address'],
            ]),
            self::fn('create_pos_order', 'Create a POS/counter sale with cash/card/transfer payment.', [
                'type' => 'OBJECT',
                'properties' => [
                    'customer_id' => ['type' => 'INTEGER'],
                    'payment_method' => ['type' => 'STRING', 'description' => 'cash|card|transfer|online|pay_later|other'],
                    'notes' => ['type' => 'STRING'],
                    'cake_id' => ['type' => 'INTEGER', 'description' => 'Optional catalog cake'],
                    'item_name' => ['type' => 'STRING', 'description' => 'Ad-hoc line name when no cake_id'],
                    'quantity' => ['type' => 'INTEGER'],
                    'unit_price_rupees' => ['type' => 'NUMBER'],
                ],
                'required' => ['customer_id', 'payment_method'],
            ]),
            self::fn('list_employees', 'List staff employees and roles.', [
                'type' => 'OBJECT',
                'properties' => (object) [],
            ]),
            self::fn('list_todays_shifts', 'List scheduled bakery shifts for a date (default today).', [
                'type' => 'OBJECT',
                'properties' => [
                    'date' => ['type' => 'STRING', 'description' => 'YYYY-MM-DD, defaults to today'],
                ],
            ]),
            self::fn('list_who_is_on', 'List staff currently clocked in / in-progress shifts.', [
                'type' => 'OBJECT',
                'properties' => (object) [],
            ]),
            self::fn('create_shift', 'Schedule a shift for a staff member. Managers and admins only.', [
                'type' => 'OBJECT',
                'properties' => [
                    'staff_id' => ['type' => 'INTEGER'],
                    'date' => ['type' => 'STRING', 'description' => 'YYYY-MM-DD'],
                    'starts_at_time' => ['type' => 'STRING', 'description' => 'HH:MM 24h'],
                    'ends_at_time' => ['type' => 'STRING', 'description' => 'HH:MM 24h'],
                    'notes' => ['type' => 'STRING'],
                ],
                'required' => ['staff_id', 'date', 'starts_at_time', 'ends_at_time'],
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: mixed}
     */
    public static function call(string $name, array $arguments, User $actor): array
    {
        $ability = self::TOOL_ABILITIES[$name] ?? null;

        if ($ability === null) {
            return ['ok' => false, 'summary' => "Unknown tool: {$name}"];
        }

        if (! StaffPermissions::allows($actor, $ability)) {
            return ['ok' => false, 'summary' => "You do not have permission to use {$name} ({$ability})."];
        }

        try {
            return match ($name) {
                'get_dashboard_summary' => self::getDashboardSummary(),
                'search_orders' => self::searchOrders($arguments),
                'get_order' => self::getOrder($arguments),
                'update_order_status' => self::updateOrderStatus($arguments, $actor),
                'update_production_status' => self::updateProductionStatus($arguments, $actor),
                'search_cakes' => self::searchCakes($arguments),
                'get_cake' => self::getCake($arguments),
                'create_cake' => self::createCake($arguments, $actor),
                'update_cake' => self::updateCake($arguments, $actor),
                'list_categories' => self::listCategories(),
                'create_category' => self::createCategory($arguments, $actor),
                'search_customers' => self::searchCustomers($arguments),
                'get_customer' => self::getCustomer($arguments),
                'create_customer' => self::createCustomer($arguments, $actor),
                'search_ingredients' => self::searchIngredients($arguments),
                'list_low_stock' => self::listLowStock(),
                'create_ingredient' => self::createIngredient($arguments, $actor),
                'adjust_ingredient_stock' => self::adjustIngredientStock($arguments, $actor),
                'log_waste' => self::logWaste($arguments, $actor),
                'create_manual_order' => self::createManualOrder($arguments, $actor),
                'create_pos_order' => self::createPosOrder($arguments, $actor),
                'list_employees' => self::listEmployees(),
                'list_todays_shifts' => self::listTodaysShifts($arguments, $actor),
                'list_who_is_on' => self::listWhoIsOn($actor),
                'create_shift' => self::createShift($arguments, $actor),
                default => ['ok' => false, 'summary' => "Unknown tool: {$name}"],
            };
        } catch (ValidationException $exception) {
            return [
                'ok' => false,
                'summary' => collect($exception->errors())->flatten()->implode(' '),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return ['ok' => false, 'summary' => 'Tool failed: '.$exception->getMessage()];
        }
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::TOOL_ABILITIES);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private static function fn(string $name, string $description, array $parameters): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'parameters' => $parameters,
        ];
    }

    /**
     * @return array{ok: bool, summary: string, data: array<string, mixed>}
     */
    private static function getDashboardSummary(): array
    {
        $month = BakeryAnalytics::monthSummary();
        $lowStock = Ingredient::query()->lowStock()->count();
        $pending = Order::query()->where('status', OrderStatus::Pending)->count();

        $data = [
            'revenue' => Money::format($month['revenue']),
            'previous_revenue' => Money::format($month['previous_revenue']),
            'budget' => Money::format($month['budget']),
            'budget_progress_percent' => $month['budget_progress_percent'],
            'gross_profit' => Money::format($month['gross_profit']),
            'net_profit' => Money::format($month['net_profit']),
            'margin_percent' => $month['margin_percent'],
            'order_count' => $month['order_count'],
            'pending_orders' => $pending,
            'low_stock_ingredients' => $lowStock,
            'waste_cost' => Money::format($month['waste_cost']),
        ];

        return [
            'ok' => true,
            'summary' => 'This month: '.$data['revenue'].' revenue, '.$data['order_count'].' orders, '.$data['margin_percent'].'% margin, '.$pending.' pending, '.$lowStock.' low-stock items.',
            'data' => $data,
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data: list<array<string, mixed>>}
     */
    private static function searchOrders(array $arguments): array
    {
        $limit = min(20, max(1, (int) ($arguments['limit'] ?? 8)));
        $query = Order::query()->with(['user', 'items'])->latest();

        if (filled($arguments['status'] ?? null)) {
            $status = OrderStatus::tryFrom((string) $arguments['status']);
            if ($status === null) {
                return ['ok' => false, 'summary' => 'Invalid order status.'];
            }
            $query->where('status', $status);
        }

        if (filled($arguments['query'] ?? null)) {
            $term = trim((string) $arguments['query']);
            $query->where(function ($builder) use ($term): void {
                $builder->where('notes', 'like', '%'.$term.'%')
                    ->orWhere('delivery_address', 'like', '%'.$term.'%')
                    ->orWhere('id', (int) $term)
                    ->orWhereHas('user', function ($user) use ($term): void {
                        $user->where('name', 'like', '%'.$term.'%')
                            ->orWhere('email', 'like', '%'.$term.'%')
                            ->orWhere('phone', 'like', '%'.$term.'%');
                    });
            });
        }

        $orders = $query->limit($limit)->get()->map(fn (Order $order): array => self::orderBrief($order))->all();

        return [
            'ok' => true,
            'summary' => count($orders) === 0
                ? 'No orders matched.'
                : 'Found '.count($orders).' order(s).',
            'data' => $orders,
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: array<string, mixed>}
     */
    private static function getOrder(array $arguments): array
    {
        $order = Order::query()->with(['user', 'items', 'invoice'])->find((int) ($arguments['order_id'] ?? 0));

        if ($order === null) {
            return ['ok' => false, 'summary' => 'Order not found.'];
        }

        $data = [
            ...self::orderBrief($order),
            'delivery_address' => $order->delivery_address,
            'notes' => $order->notes,
            'items' => $order->items->map(fn ($item): array => [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit_price' => Money::format((int) $item->unit_price),
            ])->all(),
            'invoice_id' => $order->invoice?->id,
        ];

        return [
            'ok' => true,
            'summary' => 'Order #'.$order->id.' is '.$order->status->label().' / '.$order->production_status->label().' for '.$order->user->name.'.',
            'data' => $data,
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: array<string, mixed>}
     */
    private static function updateOrderStatus(array $arguments, User $actor): array
    {
        $order = Order::query()->with(['items.cake.recipes.items', 'user', 'items'])->find((int) ($arguments['order_id'] ?? 0));
        $status = OrderStatus::tryFrom((string) ($arguments['status'] ?? ''));

        if ($order === null || $status === null) {
            return ['ok' => false, 'summary' => 'Valid order_id and status are required.'];
        }

        $from = $order->status;

        if ($from === $status) {
            return ['ok' => true, 'summary' => 'Order #'.$order->id.' is already '.$status->label().'.'];
        }

        try {
            $updated = app(UpdateOrderStatus::class)->handle($order, $status, $actor);
        } catch (InsufficientStockException $exception) {
            return ['ok' => false, 'summary' => $exception->getMessage()];
        }

        return [
            'ok' => true,
            'summary' => 'Order #'.$updated->id.' status updated from '.$from->label().' to '.$status->label().'.',
            'data' => self::orderBrief($updated),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: array<string, mixed>}
     */
    private static function updateProductionStatus(array $arguments, User $actor): array
    {
        $order = Order::query()
            ->with(['items.cake.recipes.items', 'user', 'items'])
            ->whereNotIn('status', [OrderStatus::Cancelled->value])
            ->find((int) ($arguments['order_id'] ?? 0));
        $production = ProductionStatus::tryFrom((string) ($arguments['production_status'] ?? ''));

        if ($order === null || $production === null) {
            return ['ok' => false, 'summary' => 'Valid order_id and production_status are required.'];
        }

        $fromProduction = $order->production_status;
        $fromStatus = $order->status;
        $toStatus = $fromStatus;
        $payload = ['production_status' => $production];

        if ($production === ProductionStatus::Delivered) {
            $toStatus = OrderStatus::Delivered;
            $payload['status'] = $toStatus;
        } elseif ($fromStatus === OrderStatus::Pending && $production !== ProductionStatus::Planning) {
            $toStatus = OrderStatus::Confirmed;
            $payload['status'] = $toStatus;
        } elseif ($production === ProductionStatus::Baking) {
            $toStatus = OrderStatus::Baking;
            $payload['status'] = $toStatus;
        }

        try {
            if ($toStatus !== $fromStatus) {
                app(AdjustInventoryForOrder::class)->syncForStatusChange($order, $fromStatus, $toStatus);
            }
        } catch (InsufficientStockException $exception) {
            return ['ok' => false, 'summary' => $exception->getMessage()];
        }

        $order->update($payload);
        app(GenerateInvoice::class)->handle($order->fresh(['user', 'items']));

        AuditLogger::record('order.production_status_changed', $order, [
            'production_status' => $fromProduction->value,
            'status' => $fromStatus->value,
        ], [
            'production_status' => $production->value,
            'status' => $toStatus->value,
            'via' => 'admin_agent',
        ], $actor);

        return [
            'ok' => true,
            'summary' => 'Order #'.$order->id.' moved to production '.$production->label().'.',
            'data' => self::orderBrief($order->fresh(['user', 'items'])),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data: list<array<string, mixed>>}
     */
    private static function searchCakes(array $arguments): array
    {
        $limit = min(20, max(1, (int) ($arguments['limit'] ?? 8)));
        $query = Cake::query()->with('category')->orderBy('name');

        if (($arguments['active_only'] ?? true) !== false) {
            $query->active();
        }

        if (filled($arguments['query'] ?? null)) {
            $term = trim((string) $arguments['query']);
            $query->where(function ($builder) use ($term): void {
                $builder->where('name', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%')
                    ->orWhere('slug', 'like', '%'.$term.'%');
            });
        }

        $cakes = $query->limit($limit)->get()->map(fn (Cake $cake): array => self::cakeBrief($cake))->all();

        return [
            'ok' => true,
            'summary' => count($cakes) === 0 ? 'No cakes matched.' : 'Found '.count($cakes).' cake(s).',
            'data' => $cakes,
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: array<string, mixed>}
     */
    private static function getCake(array $arguments): array
    {
        $cake = Cake::query()->with('category')->find((int) ($arguments['cake_id'] ?? 0));

        if ($cake === null) {
            return ['ok' => false, 'summary' => 'Cake not found.'];
        }

        return [
            'ok' => true,
            'summary' => $cake->name.' - '.$cake->formattedPrice().' ('.($cake->is_active ? 'active' : 'inactive').').',
            'data' => self::cakeBrief($cake),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: array<string, mixed>}
     */
    private static function createCake(array $arguments, User $actor): array
    {
        $name = trim((string) ($arguments['name'] ?? ''));
        $categoryId = (int) ($arguments['category_id'] ?? 0);
        $price = Money::rupeesToCents($arguments['price_rupees'] ?? 0);

        if ($name === '' || $categoryId < 1 || $price < 0) {
            return ['ok' => false, 'summary' => 'name, category_id, and price_rupees are required.'];
        }

        if (! Category::query()->whereKey($categoryId)->exists()) {
            return ['ok' => false, 'summary' => 'Category not found.'];
        }

        $cake = Cake::query()->create([
            'name' => $name,
            'slug' => Str::slug($name),
            'category_id' => $categoryId,
            'price' => $price,
            'base_price' => $price,
            'description' => filled($arguments['description'] ?? null) ? (string) $arguments['description'] : null,
            'lead_days' => max(0, (int) ($arguments['lead_days'] ?? 3)),
            'is_active' => (bool) ($arguments['is_active'] ?? true),
            'is_featured' => (bool) ($arguments['is_featured'] ?? false),
        ]);

        AuditLogger::record('cake.created', $cake, null, [
            'name' => $cake->name,
            'price' => $cake->price,
            'via' => 'admin_agent',
        ], $actor);

        return [
            'ok' => true,
            'summary' => 'Created cake #'.$cake->id.' '.$cake->name.' at '.$cake->formattedPrice().'.',
            'data' => self::cakeBrief($cake->load('category')),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: array<string, mixed>}
     */
    private static function updateCake(array $arguments, User $actor): array
    {
        $cake = Cake::query()->find((int) ($arguments['cake_id'] ?? 0));

        if ($cake === null) {
            return ['ok' => false, 'summary' => 'Cake not found.'];
        }

        $old = [
            'name' => $cake->name,
            'price' => $cake->price,
            'is_active' => $cake->is_active,
        ];

        $payload = [];

        if (array_key_exists('name', $arguments) && filled($arguments['name'])) {
            $payload['name'] = trim((string) $arguments['name']);
            $payload['slug'] = Str::slug($payload['name']);
        }

        if (array_key_exists('price_rupees', $arguments) && $arguments['price_rupees'] !== null) {
            $payload['price'] = Money::rupeesToCents($arguments['price_rupees']);
            $payload['base_price'] = $payload['price'];
        }

        if (array_key_exists('description', $arguments)) {
            $payload['description'] = filled($arguments['description']) ? (string) $arguments['description'] : null;
        }

        if (array_key_exists('lead_days', $arguments) && $arguments['lead_days'] !== null) {
            $payload['lead_days'] = max(0, (int) $arguments['lead_days']);
        }

        if (array_key_exists('is_active', $arguments) && $arguments['is_active'] !== null) {
            $payload['is_active'] = (bool) $arguments['is_active'];
        }

        if (array_key_exists('is_featured', $arguments) && $arguments['is_featured'] !== null) {
            $payload['is_featured'] = (bool) $arguments['is_featured'];
        }

        if (array_key_exists('category_id', $arguments) && $arguments['category_id'] !== null) {
            $categoryId = (int) $arguments['category_id'];
            if (! Category::query()->whereKey($categoryId)->exists()) {
                return ['ok' => false, 'summary' => 'Category not found.'];
            }
            $payload['category_id'] = $categoryId;
        }

        if ($payload === []) {
            return ['ok' => false, 'summary' => 'No cake fields to update.'];
        }

        $cake->update($payload);

        AuditLogger::record('cake.updated', $cake, $old, [
            'name' => $cake->name,
            'price' => $cake->price,
            'is_active' => $cake->is_active,
            'via' => 'admin_agent',
        ], $actor);

        return [
            'ok' => true,
            'summary' => 'Updated cake #'.$cake->id.' '.$cake->name.'.',
            'data' => self::cakeBrief($cake->fresh('category')),
        ];
    }

    /**
     * @return array{ok: bool, summary: string, data: list<array<string, mixed>>}
     */
    private static function listCategories(): array
    {
        $categories = Category::query()
            ->withCount('cakes')
            ->orderBy('sort')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'sort' => $category->sort,
                'is_active' => $category->is_active,
                'cakes_count' => $category->cakes_count,
            ])
            ->all();

        return [
            'ok' => true,
            'summary' => count($categories).' categor'.(count($categories) === 1 ? 'y' : 'ies').'.',
            'data' => $categories,
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: array<string, mixed>}
     */
    private static function createCategory(array $arguments, User $actor): array
    {
        $name = trim((string) ($arguments['name'] ?? ''));

        if ($name === '') {
            return ['ok' => false, 'summary' => 'Category name is required.'];
        }

        $category = Category::query()->create([
            'name' => $name,
            'slug' => Str::slug($name),
            'sort' => max(0, (int) ($arguments['sort'] ?? 0)),
            'is_active' => true,
        ]);

        AuditLogger::record('category.created', $category, null, [
            'name' => $category->name,
            'via' => 'admin_agent',
        ], $actor);

        return [
            'ok' => true,
            'summary' => 'Created category #'.$category->id.' '.$category->name.'.',
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'sort' => $category->sort,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data: list<array<string, mixed>>}
     */
    private static function searchCustomers(array $arguments): array
    {
        $term = trim((string) ($arguments['query'] ?? ''));
        $limit = min(20, max(1, (int) ($arguments['limit'] ?? 8)));

        if ($term === '') {
            return ['ok' => false, 'summary' => 'query is required.'];
        }

        $customers = User::query()
            ->where('role', UserRole::Customer)
            ->where(function ($builder) use ($term): void {
                $builder->where('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%')
                    ->orWhere('phone', 'like', '%'.$term.'%');
            })
            ->withCount('orders')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (User $user): array => self::customerBrief($user))
            ->all();

        return [
            'ok' => true,
            'summary' => count($customers) === 0 ? 'No customers matched.' : 'Found '.count($customers).' customer(s).',
            'data' => $customers,
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: array<string, mixed>}
     */
    private static function getCustomer(array $arguments): array
    {
        $customer = User::query()
            ->where('role', UserRole::Customer)
            ->withCount('orders')
            ->find((int) ($arguments['customer_id'] ?? 0));

        if ($customer === null) {
            return ['ok' => false, 'summary' => 'Customer not found.'];
        }

        return [
            'ok' => true,
            'summary' => $customer->name.' ('.$customer->email.') - '.$customer->orders_count.' order(s).',
            'data' => self::customerBrief($customer),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: array<string, mixed>}
     */
    private static function createCustomer(array $arguments, User $actor): array
    {
        $name = trim((string) ($arguments['name'] ?? ''));
        $email = trim((string) ($arguments['email'] ?? ''));

        if ($name === '' || $email === '') {
            return ['ok' => false, 'summary' => 'name and email are required.'];
        }

        if (User::query()->where('email', $email)->exists()) {
            return ['ok' => false, 'summary' => 'A user with that email already exists.'];
        }

        $customer = app(CreateManualCustomer::class)->handle([
            'name' => $name,
            'email' => $email,
            'phone' => filled($arguments['phone'] ?? null) ? (string) $arguments['phone'] : null,
            'address_line' => filled($arguments['address_line'] ?? null) ? (string) $arguments['address_line'] : null,
            'city' => filled($arguments['city'] ?? null) ? (string) $arguments['city'] : null,
        ]);

        AuditLogger::record('customer.created', $customer, null, [
            'name' => $customer->name,
            'via' => 'admin_agent',
        ], $actor);

        return [
            'ok' => true,
            'summary' => 'Created customer #'.$customer->id.' '.$customer->name.'.',
            'data' => self::customerBrief($customer->loadCount('orders')),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data: list<array<string, mixed>>}
     */
    private static function searchIngredients(array $arguments): array
    {
        $limit = min(20, max(1, (int) ($arguments['limit'] ?? 10)));
        $query = Ingredient::query()->orderBy('name');

        if (filled($arguments['query'] ?? null)) {
            $query->where('name', 'like', '%'.trim((string) $arguments['query']).'%');
        }

        $items = $query->limit($limit)->get()->map(fn (Ingredient $ingredient): array => self::ingredientBrief($ingredient))->all();

        return [
            'ok' => true,
            'summary' => count($items) === 0 ? 'No ingredients matched.' : 'Found '.count($items).' ingredient(s).',
            'data' => $items,
        ];
    }

    /**
     * @return array{ok: bool, summary: string, data: list<array<string, mixed>>}
     */
    private static function listLowStock(): array
    {
        $items = Ingredient::query()
            ->lowStock()
            ->orderBy('name')
            ->get()
            ->map(fn (Ingredient $ingredient): array => self::ingredientBrief($ingredient))
            ->all();

        return [
            'ok' => true,
            'summary' => count($items) === 0 ? 'No low-stock ingredients.' : count($items).' ingredient(s) need reordering.',
            'data' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: array<string, mixed>}
     */
    private static function createIngredient(array $arguments, User $actor): array
    {
        $name = trim((string) ($arguments['name'] ?? ''));
        $unit = IngredientUnit::tryFrom((string) ($arguments['unit'] ?? ''));

        if ($name === '' || $unit === null) {
            return ['ok' => false, 'summary' => 'name and a valid unit are required.'];
        }

        $ingredient = Ingredient::query()->create([
            'name' => $name,
            'unit' => $unit,
            'current_stock' => max(0, (float) ($arguments['current_stock'] ?? 0)),
            'unit_cost' => Money::rupeesToCents($arguments['unit_cost_rupees'] ?? 0),
            'reorder_threshold' => max(0, (float) ($arguments['reorder_threshold'] ?? 0)),
            'supplier' => filled($arguments['supplier'] ?? null) ? (string) $arguments['supplier'] : null,
        ]);

        AuditLogger::record('inventory.ingredient_created', $ingredient, null, [
            'name' => $ingredient->name,
            'via' => 'admin_agent',
        ], $actor);

        return [
            'ok' => true,
            'summary' => 'Created ingredient #'.$ingredient->id.' '.$ingredient->name.'.',
            'data' => self::ingredientBrief($ingredient),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: array<string, mixed>}
     */
    private static function adjustIngredientStock(array $arguments, User $actor): array
    {
        $ingredient = Ingredient::query()->find((int) ($arguments['ingredient_id'] ?? 0));

        if ($ingredient === null) {
            return ['ok' => false, 'summary' => 'Ingredient not found.'];
        }

        $old = [
            'current_stock' => $ingredient->current_stock,
            'unit_cost' => $ingredient->unit_cost,
        ];

        $payload = [];

        if (array_key_exists('set_stock', $arguments) && $arguments['set_stock'] !== null) {
            $payload['current_stock'] = max(0, (float) $arguments['set_stock']);
        } elseif (array_key_exists('add_stock', $arguments) && $arguments['add_stock'] !== null) {
            $payload['current_stock'] = max(0, (float) $ingredient->current_stock + (float) $arguments['add_stock']);
        }

        if (array_key_exists('unit_cost_rupees', $arguments) && $arguments['unit_cost_rupees'] !== null) {
            $payload['unit_cost'] = Money::rupeesToCents($arguments['unit_cost_rupees']);
        }

        if (array_key_exists('reorder_threshold', $arguments) && $arguments['reorder_threshold'] !== null) {
            $payload['reorder_threshold'] = max(0, (float) $arguments['reorder_threshold']);
        }

        if ($payload === []) {
            return ['ok' => false, 'summary' => 'Provide set_stock, add_stock, unit_cost_rupees, or reorder_threshold.'];
        }

        $ingredient->update($payload);

        AuditLogger::record('inventory.ingredient_updated', $ingredient, $old, [
            'current_stock' => $ingredient->current_stock,
            'unit_cost' => $ingredient->unit_cost,
            'via' => 'admin_agent',
        ], $actor);

        return [
            'ok' => true,
            'summary' => 'Updated '.$ingredient->name.' stock to '.$ingredient->stockLabel().'.',
            'data' => self::ingredientBrief($ingredient->fresh()),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: array<string, mixed>}
     */
    private static function logWaste(array $arguments, User $actor): array
    {
        $itemType = (string) ($arguments['item_type'] ?? '');
        $quantity = (float) ($arguments['quantity'] ?? 0);
        $reason = WasteReason::tryFrom((string) ($arguments['reason'] ?? ''));

        if (! in_array($itemType, ['ingredient', 'cake'], true) || $quantity <= 0 || $reason === null) {
            return ['ok' => false, 'summary' => 'item_type, quantity, and a valid reason are required.'];
        }

        $ingredient = $itemType === 'ingredient'
            ? Ingredient::query()->find((int) ($arguments['ingredient_id'] ?? 0))
            : null;
        $cake = $itemType === 'cake'
            ? Cake::query()->find((int) ($arguments['cake_id'] ?? 0))
            : null;

        if ($itemType === 'ingredient' && $ingredient === null) {
            return ['ok' => false, 'summary' => 'ingredient_id is required for ingredient waste.'];
        }

        if ($itemType === 'cake' && $cake === null) {
            return ['ok' => false, 'summary' => 'cake_id is required for cake waste.'];
        }

        $cost = WasteEntry::computeCostImpact($ingredient, $cake, $quantity);
        $entry = WasteEntry::query()->create([
            'wasted_on' => filled($arguments['wasted_on'] ?? null)
                ? (string) $arguments['wasted_on']
                : now()->toDateString(),
            'ingredient_id' => $ingredient?->id,
            'cake_id' => $cake?->id,
            'quantity' => $quantity,
            'reason' => $reason,
            'cost_impact' => $cost,
            'notes' => filled($arguments['notes'] ?? null) ? (string) $arguments['notes'] : null,
        ]);

        if ($ingredient !== null) {
            $ingredient->update([
                'current_stock' => max(0, (float) $ingredient->current_stock - $quantity),
            ]);
        }

        AuditLogger::record('waste.logged', $entry, null, [
            'item_type' => $itemType,
            'quantity' => $quantity,
            'cost_impact' => $cost,
            'via' => 'admin_agent',
        ], $actor);

        $label = $ingredient?->name ?? $cake?->name ?? 'item';

        return [
            'ok' => true,
            'summary' => 'Logged waste of '.$quantity.' '.$label.' ('.$reason->label().') costing '.Money::format($cost).'.',
            'data' => [
                'id' => $entry->id,
                'item' => $label,
                'quantity' => $quantity,
                'reason' => $reason->value,
                'cost_impact' => Money::format($cost),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: array<string, mixed>}
     */
    private static function createManualOrder(array $arguments, User $actor): array
    {
        $order = app(CreateManualOrder::class)->handle([
            'user_id' => (int) ($arguments['customer_id'] ?? 0),
            'cake_id' => (int) ($arguments['cake_id'] ?? 0),
            'quantity' => max(1, (int) ($arguments['quantity'] ?? 1)),
            'delivery_date' => (string) ($arguments['delivery_date'] ?? ''),
            'delivery_address' => (string) ($arguments['delivery_address'] ?? ''),
            'notes' => filled($arguments['notes'] ?? null) ? (string) $arguments['notes'] : null,
            'fulfillment_method' => (string) ($arguments['fulfillment_method'] ?? 'pickup'),
        ]);

        AuditLogger::record('order.created', $order, null, [
            'source' => 'manual',
            'via' => 'admin_agent',
        ], $actor);

        return [
            'ok' => true,
            'summary' => 'Created manual order #'.$order->id.' for '.$order->user->name.' totaling '.$order->formattedTotalDue().'.',
            'data' => self::orderBrief($order->load(['user', 'items'])),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: array<string, mixed>}
     */
    private static function createPosOrder(array $arguments, User $actor): array
    {
        $cakeId = isset($arguments['cake_id']) ? (int) $arguments['cake_id'] : null;
        $quantity = max(1, (int) ($arguments['quantity'] ?? 1));
        $line = [
            'cake_id' => $cakeId ?: null,
            'name' => filled($arguments['item_name'] ?? null) ? (string) $arguments['item_name'] : null,
            'quantity' => $quantity,
            'unit_price_rupees' => $arguments['unit_price_rupees'] ?? null,
        ];

        if ($line['cake_id'] === null && blank($line['name'])) {
            return ['ok' => false, 'summary' => 'Provide cake_id or item_name for the POS line.'];
        }

        if ($line['cake_id'] === null && $line['unit_price_rupees'] === null) {
            return ['ok' => false, 'summary' => 'unit_price_rupees is required for ad-hoc POS items.'];
        }

        $order = app(CreatePosOrder::class)->handle([
            'user_id' => (int) ($arguments['customer_id'] ?? 0),
            'payment_method' => (string) ($arguments['payment_method'] ?? 'cash'),
            'notes' => filled($arguments['notes'] ?? null) ? (string) $arguments['notes'] : null,
            'lines' => [$line],
        ]);

        AuditLogger::record('order.created', $order, null, [
            'source' => 'pos',
            'via' => 'admin_agent',
        ], $actor);

        return [
            'ok' => true,
            'summary' => 'Created POS order #'.$order->id.' ('.$order->receipt_number.') totaling '.$order->formattedTotalDue().'.',
            'data' => self::orderBrief($order->load(['user', 'items'])),
        ];
    }

    /**
     * @return array{ok: bool, summary: string, data: list<array<string, mixed>>}
     */
    private static function listEmployees(): array
    {
        $employees = User::query()
            ->whereIn('role', collect(UserRole::staffCases())->map->value)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'role'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
            ])
            ->all();

        return [
            'ok' => true,
            'summary' => count($employees).' staff member(s).',
            'data' => $employees,
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data: list<array<string, mixed>>}
     */
    private static function listTodaysShifts(array $arguments, User $actor): array
    {
        $day = filled($arguments['date'] ?? null)
            ? Carbon::parse((string) $arguments['date'])->startOfDay()
            : now()->startOfDay();
        $dayEnd = $day->copy()->endOfDay();

        Shift::query()
            ->whereBetween('starts_at', [$day, $dayEnd])
            ->where('status', ShiftStatus::Scheduled)
            ->where('ends_at', '<', now())
            ->whereDoesntHave('entries')
            ->update(['status' => ShiftStatus::Missed]);

        $query = Shift::query()
            ->with('user')
            ->whereBetween('starts_at', [$day, $dayEnd])
            ->orderBy('starts_at');

        if (! $actor->isAdmin()) {
            $query->where('user_id', $actor->id);
        }

        $shifts = $query->get()->map(fn (Shift $shift): array => self::shiftBrief($shift))->all();

        return [
            'ok' => true,
            'summary' => count($shifts) === 0
                ? 'No shifts on '.$day->toDateString().'.'
                : count($shifts).' shift(s) on '.$day->toDateString().'.',
            'data' => $shifts,
        ];
    }

    /**
     * @return array{ok: bool, summary: string, data: list<array<string, mixed>>}
     */
    private static function listWhoIsOn(User $actor): array
    {
        $query = Shift::query()
            ->with(['user', 'entries'])
            ->where('status', ShiftStatus::InProgress)
            ->orderBy('starts_at');

        if (! $actor->isAdmin()) {
            $query->where('user_id', $actor->id);
        }

        $on = $query->get()->map(function (Shift $shift): array {
            $entry = $shift->openEntry();

            return [
                ...self::shiftBrief($shift),
                'clocked_in_at' => $entry?->clocked_in_at?->toDateTimeString(),
                'worked' => $entry?->durationLabel(),
            ];
        })->all();

        return [
            'ok' => true,
            'summary' => count($on) === 0 ? 'Nobody is clocked in right now.' : count($on).' staff on shift.',
            'data' => $on,
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{ok: bool, summary: string, data?: array<string, mixed>}
     */
    private static function createShift(array $arguments, User $actor): array
    {
        if (! $actor->isAdmin()) {
            return ['ok' => false, 'summary' => 'Only managers and admins can schedule shifts.'];
        }

        $staffId = (int) ($arguments['staff_id'] ?? 0);
        $date = (string) ($arguments['date'] ?? '');
        $startTime = (string) ($arguments['starts_at_time'] ?? '');
        $endTime = (string) ($arguments['ends_at_time'] ?? '');

        $staff = User::query()
            ->whereKey($staffId)
            ->whereIn('role', collect(UserRole::staffCases())->map->value)
            ->first();

        if ($staff === null || $date === '' || $startTime === '' || $endTime === '') {
            return ['ok' => false, 'summary' => 'staff_id, date, starts_at_time, and ends_at_time are required.'];
        }

        $startsAt = Carbon::parse($date.' '.$startTime);
        $endsAt = Carbon::parse($date.' '.$endTime);

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            return ['ok' => false, 'summary' => 'ends_at_time must be after starts_at_time.'];
        }

        $overlaps = Shift::query()
            ->where('user_id', $staff->id)
            ->whereNotIn('status', [ShiftStatus::Cancelled->value, ShiftStatus::Missed->value])
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($overlaps) {
            return ['ok' => false, 'summary' => $staff->name.' already has an overlapping shift.'];
        }

        $shift = Shift::query()->create([
            'user_id' => $staff->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => ShiftStatus::Scheduled,
            'notes' => filled($arguments['notes'] ?? null) ? (string) $arguments['notes'] : null,
        ]);

        AuditLogger::record('shift.created', $shift, null, [
            'user_id' => $shift->user_id,
            'via' => 'admin_agent',
        ], $actor);

        return [
            'ok' => true,
            'summary' => 'Scheduled '.$staff->name.' for '.$shift->windowLabel().' on '.$startsAt->toDateString().'.',
            'data' => self::shiftBrief($shift->load('user')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function shiftBrief(Shift $shift): array
    {
        return [
            'id' => $shift->id,
            'staff' => $shift->user?->name,
            'staff_id' => $shift->user_id,
            'role' => $shift->user?->role->label(),
            'starts_at' => $shift->starts_at->toDateTimeString(),
            'ends_at' => $shift->ends_at->toDateTimeString(),
            'window' => $shift->windowLabel(),
            'status' => $shift->status->value,
            'status_label' => $shift->status->label(),
            'notes' => $shift->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function orderBrief(Order $order): array
    {
        return [
            'id' => $order->id,
            'customer' => $order->user?->name,
            'customer_id' => $order->user_id,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'production_status' => $order->production_status->value,
            'production_label' => $order->production_status->label(),
            'total_due' => $order->formattedTotalDue(),
            'delivery_date' => $order->delivery_date?->toDateString(),
            'item_count' => $order->relationLoaded('items') ? $order->items->count() : null,
            'admin_url' => route('admin.orders.show', $order),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function cakeBrief(Cake $cake): array
    {
        return [
            'id' => $cake->id,
            'name' => $cake->name,
            'slug' => $cake->slug,
            'category' => $cake->category?->name,
            'category_id' => $cake->category_id,
            'price' => $cake->formattedPrice(),
            'price_cents' => $cake->price,
            'lead_days' => $cake->lead_days,
            'is_active' => $cake->is_active,
            'is_featured' => $cake->is_featured,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function customerBrief(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'city' => $user->city,
            'customer_source' => $user->customer_source->value,
            'orders_count' => $user->orders_count ?? $user->orders()->count(),
            'admin_url' => route('admin.customers.show', $user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function ingredientBrief(Ingredient $ingredient): array
    {
        return [
            'id' => $ingredient->id,
            'name' => $ingredient->name,
            'unit' => $ingredient->unit->value,
            'current_stock' => (float) $ingredient->current_stock,
            'stock_label' => $ingredient->stockLabel(),
            'reorder_threshold' => (float) $ingredient->reorder_threshold,
            'unit_cost' => $ingredient->formattedUnitCost(),
            'supplier' => $ingredient->supplier,
            'is_low_stock' => $ingredient->isLowStock(),
        ];
    }
}
