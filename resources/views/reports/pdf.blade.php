<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }}</title>
    <style>
        @page {
            margin: 26px 24px 32px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.45;
        }

        .topbar {
            border-bottom: 2px solid #0f766e;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .brand {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1.4px;
            color: #0f766e;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 4px;
        }

        .subtitle {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 2px;
        }

        .summary-box {
            background: #f8fafc;
            border: 1px solid #dbe4ea;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 16px;
        }

        .summary-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .summary-text {
            color: #334155;
        }

        .meta-table,
        .cards-table,
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table {
            margin-bottom: 14px;
        }

        .meta-table td {
            width: 50%;
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            vertical-align: top;
        }

        .meta-key {
            display: block;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #6b7280;
            margin-bottom: 3px;
        }

        .meta-value {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin: 16px 0 8px;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .cards-table td {
            width: 25%;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            padding: 12px 10px;
            vertical-align: top;
        }

        .card-label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .7px;
            margin-bottom: 6px;
        }

        .card-value {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
        }

        .report-table {
            margin-top: 8px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #dbe4ea;
            padding: 7px 8px;
            text-align: left;
            vertical-align: top;
        }

        .report-table th {
            background: #e6fffb;
            color: #134e4a;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .report-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .muted {
            color: #64748b;
        }

        .footer {
            margin-top: 18px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="brand">GYM Chahrazad</div>
        <div class="title">{{ $reportTitle }}</div>
        <div class="subtitle">Periode analysee: {{ $dateRangeLabel }}</div>
        <div class="subtitle">Document genere le {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <div class="summary-box">
        <div class="summary-label">Resume</div>
        <div class="summary-text">{{ $reportDescription }}</div>
    </div>

    @if(!empty($reportMeta))
        <table class="meta-table">
            <tbody>
                @foreach(array_chunk($reportMeta, 2) as $metaRow)
                    <tr>
                        @foreach($metaRow as $meta)
                            <td>
                                <span class="meta-key">{{ $meta['label'] }}</span>
                                <span class="meta-value">{{ $meta['value'] }}</span>
                            </td>
                        @endforeach
                        @if(count($metaRow) === 1)
                            <td></td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">Indicateurs cles</div>
    <table class="cards-table">
        <tr>
            @foreach($reportCards as $card)
                <td>
                    <div class="card-label">{{ $card['label'] }}</div>
                    <div class="card-value">{{ $card['value'] }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <div class="section-title">Details</div>
    <table class="report-table">
        <thead>
            <tr>
                @foreach($reportColumns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($reportRows as $row)
                <tr>
                    @foreach($reportColumns as $column)
                        <td>{{ $row[$column] ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($reportColumns) }}" class="muted">Aucune donnee pour cette periode.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Document interne de gestion. Ce rapport a ete prepare pour le suivi administratif et financier de la salle.
    </div>
</body>
</html>
