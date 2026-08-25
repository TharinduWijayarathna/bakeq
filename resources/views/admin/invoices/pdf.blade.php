<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $invoice->number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #3d2433;
            font-size: 12px;
            margin: 0;
            padding: 28px;
        }
        .brand-bar {
            height: 6px;
            background: linear-gradient(90deg, #ec407a 0%, #26c6da 100%);
            margin: -28px -28px 22px -28px;
        }
        h1 {
            font-size: 22px;
            margin: 0 0 4px 0;
            color: #c2185b;
        }
        .muted {
            color: #7a5a66;
            font-size: 11px;
            margin: 0;
        }
        .header-table,
        .items,
        .totals {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-top: 24px;
        }
        .header-table td {
            vertical-align: top;
            width: 50%;
            padding: 0;
        }
        .items {
            margin-top: 28px;
        }
        .items th {
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #00838f;
            border-bottom: 2px solid #ec407a;
            padding: 8px 4px;
        }
        .items td {
            padding: 10px 4px;
            border-bottom: 1px solid #f3d6e0;
            vertical-align: top;
        }
        .items .num,
        .totals .num {
            text-align: right;
            white-space: nowrap;
        }
        .totals {
            margin-top: 16px;
            width: 280px;
            margin-left: auto;
        }
        .totals td {
            padding: 6px 0;
        }
        .totals .due td {
            font-weight: bold;
            font-size: 14px;
            color: #c2185b;
            border-top: 2px solid #c2185b;
            padding-top: 10px;
        }
        .label {
            font-weight: bold;
            margin: 0 0 4px 0;
            color: #3d2433;
        }
        .footer {
            margin-top: 36px;
            padding-top: 12px;
            border-top: 1px solid #f3d6e0;
            color: #7a5a66;
            font-size: 10px;
        }
    </style>
</head>
<body>
    @php
        $business = $invoice->business_snapshot ?? [];
        $customer = $invoice->customer_snapshot ?? [];
        $brandName = $business['name'] ?? \App\Support\Brand::name();
    @endphp

    <div class="brand-bar"></div>

    <h1>{{ $brandName }}</h1>
    <p class="muted">
        {{ $business['address'] ?? '' }}
        @if (! empty($business['phone'])) · {{ $business['phone'] }} @endif
        @if (! empty($business['email'])) · {{ $business['email'] }} @endif
    </p>

    <table class="header-table">
        <tr>
            <td>
                <p class="label">Invoice {{ $invoice->number }}</p>
                <p class="muted">Order #{{ $invoice->order_id }}</p>
                <p class="muted">{{ $invoice->issued_at->toDayDateTimeString() }}</p>
            </td>
            <td>
                <p class="label">Bill to</p>
                <p style="margin:0;">{{ $customer['name'] ?? '' }}</p>
                <p class="muted">{{ $customer['email'] ?? '' }}</p>
                <p class="muted">{{ $customer['address'] ?? '' }}</p>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th class="num">Qty</th>
                <th class="num">Unit</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->line_items as $line)
                <tr>
                    <td>{{ $line['name'] }}</td>
                    <td class="num">{{ $line['quantity'] }}</td>
                    <td class="num">{{ \App\Support\Money::format((int) $line['unit_price']) }}</td>
                    <td class="num">{{ \App\Support\Money::format((int) $line['line_total']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="num">{{ \App\Support\Money::format($invoice->subtotal) }}</td>
        </tr>
        @if ($invoice->discount_amount > 0)
            <tr>
                <td>Discount</td>
                <td class="num">− {{ \App\Support\Money::format($invoice->discount_amount) }}</td>
            </tr>
        @endif
        <tr>
            <td>Delivery / pickup</td>
            <td class="num">{{ \App\Support\Money::format($invoice->delivery_fee) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="num">{{ \App\Support\Money::format($invoice->tax_amount) }}</td>
        </tr>
        <tr>
            <td>Deposit paid</td>
            <td class="num">− {{ \App\Support\Money::format($invoice->deposit_paid) }}</td>
        </tr>
        <tr class="due">
            <td>Balance due</td>
            <td class="num">{{ $invoice->formattedTotalDue() }}</td>
        </tr>
    </table>

    <p class="footer">{{ \App\Support\Brand::tagline() }} · {{ $brandName }}</p>
</body>
</html>
