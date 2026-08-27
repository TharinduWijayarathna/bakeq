<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $payload['title'] }} · {{ $payload['period_label'] }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #3d2433;
            font-size: 11px;
            margin: 0;
            padding: 28px;
        }
        .brand-bar {
            height: 6px;
            background: linear-gradient(90deg, #ec407a 0%, #26c6da 100%);
            margin: -28px -28px 22px -28px;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 4px 0;
            color: #c2185b;
        }
        .muted {
            color: #7a5a66;
            font-size: 10px;
            margin: 0;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }
        .summary td {
            width: 25%;
            vertical-align: top;
            padding: 8px 10px;
            background: #fdf2f6;
            border: 1px solid #f3d6e0;
        }
        .summary .label {
            display: block;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #7a5a66;
            margin-bottom: 4px;
        }
        .summary .value {
            font-size: 13px;
            font-weight: bold;
            color: #3d2433;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 22px;
        }
        .items th {
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #00838f;
            border-bottom: 2px solid #ec407a;
            padding: 8px 4px;
        }
        .items td {
            padding: 8px 4px;
            border-bottom: 1px solid #f3d6e0;
            vertical-align: top;
        }
        .footer {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #f3d6e0;
            color: #7a5a66;
            font-size: 9px;
        }
        .empty {
            padding: 16px 4px;
            color: #7a5a66;
        }
    </style>
</head>
<body>
    <div class="brand-bar"></div>

    <h1>{{ $payload['title'] }}</h1>
    <p class="muted">{{ $brandName }} · {{ $payload['period_label'] }}</p>
    <p class="muted" style="margin-top:4px;">{{ $payload['description'] }}</p>

    @if ($payload['summary'] !== [])
        <table class="summary">
            <tr>
                @foreach ($payload['summary'] as $stat)
                    <td>
                        <span class="label">{{ $stat['label'] }}</span>
                        <span class="value">{{ $stat['value'] }}</span>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    <table class="items">
        <thead>
            <tr>
                @foreach ($payload['columns'] as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($payload['rows'] as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="{{ count($payload['columns']) }}">No rows for this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($payload['footnote'])
        <p class="muted" style="margin-top:12px;">{{ $payload['footnote'] }}</p>
    @endif

    <p class="footer">
        Generated {{ $generatedAt->toDayDateTimeString() }} · {{ $brandName }}
    </p>
</body>
</html>
