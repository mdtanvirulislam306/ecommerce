<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    <style>
        :root {
            --navy: #0f2744;
            --muted: #64748b;
            --line: #e2e8f0;
            --teal: #0f766e;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--navy);
            font-family: "Segoe UI", system-ui, sans-serif;
            background: #f8fafc;
        }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 28px 20px 48px; }
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }
        .toolbar button, .toolbar a {
            border: 1px solid var(--line);
            background: #fff;
            color: var(--navy);
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .toolbar button.primary { background: var(--teal); color: #fff; border-color: var(--teal); }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .meta { color: var(--muted); font-size: 13px; margin: 0 0 18px; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border-bottom: 1px solid var(--line); text-align: left; padding: 9px 10px; font-size: 13px; }
        th { font-size: 11px; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); background: #f1f5f9; }
        .empty { padding: 32px; text-align: center; color: var(--muted); }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .wrap { max-width: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="toolbar">
            <button class="primary" type="button" onclick="window.print()">Print / Save as PDF</button>
            <a href="javascript:window.close()">Close</a>
        </div>

        <h1>{{ $title }}</h1>
        <p class="meta">
            {{ config('app.name') }}
            · {{ $rows->count() }} row{{ $rows->count() === 1 ? '' : 's' }}
            · Generated {{ $generatedAt }}
            @if (count($filters))
                · {{ implode(' · ', $filters) }}
            @endif
        </p>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Company</th>
                    <th>Stage</th>
                    <th>Source</th>
                    <th>Owner</th>
                    <th>Converted</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['email'] ?: '—' }}</td>
                        <td>{{ $row['phone'] ?: '—' }}</td>
                        <td>{{ $row['company'] ?: '—' }}</td>
                        <td>{{ $row['stage'] }}</td>
                        <td>{{ $row['source'] ?: '—' }}</td>
                        <td>{{ $row['owner'] ?: '—' }}</td>
                        <td>{{ $row['converted'] }}</td>
                        <td>{{ $row['created_at'] ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="9">No leads match the current filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($autoprint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
