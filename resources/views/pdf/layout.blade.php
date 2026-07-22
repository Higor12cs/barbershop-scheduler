@php
    $fontDir = str_replace('\\', '/', storage_path('fonts'));
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @font-face {
            font-family: 'Outfit';
            font-weight: 400;
            font-style: normal;
            src: url("{{ $fontDir }}/Outfit-Regular.ttf") format("truetype");
        }

        @font-face {
            font-family: 'Outfit';
            font-weight: 500;
            font-style: normal;
            src: url("{{ $fontDir }}/Outfit-Medium.ttf") format("truetype");
        }

        @font-face {
            font-family: 'Outfit';
            font-weight: 600;
            font-style: normal;
            src: url("{{ $fontDir }}/Outfit-SemiBold.ttf") format("truetype");
        }

        * {
            font-family: 'Outfit', sans-serif;
        }

        @page {
            margin: 110px 40px 70px 40px;
        }

        body {
            margin: 0;
            color: #09090b;
            font-size: 11px;
            line-height: 1.5;
        }

        header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            height: 70px;
        }

        header .tenant {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #71717a;
        }

        header .title {
            margin-top: 4px;
            font-size: 20px;
            font-weight: 600;
            color: #09090b;
        }

        header .meta {
            margin-top: 6px;
            font-size: 10px;
            color: #71717a;
        }

        header .rule {
            margin-top: 10px;
            border-bottom: 1px solid #e4e4e7;
        }

        footer {
            position: fixed;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 40px;
            padding-top: 8px;
            border-top: 1px solid #e4e4e7;
            font-size: 9px;
            color: #a1a1aa;
        }

        footer .brand {
            float: left;
        }

        footer .pages {
            float: right;
        }

        footer .pages:after {
            content: "Página " counter(page) " de " counter(pages);
        }

        section {
            margin-bottom: 22px;
        }

        h2 {
            margin: 0 0 8px 0;
            font-size: 12px;
            font-weight: 600;
            color: #09090b;
        }

        table.summary {
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        table.summary td {
            padding: 2px 0;
            font-size: 11px;
            vertical-align: baseline;
        }

        table.summary td.summary-label {
            color: #71717a;
            padding-right: 18px;
        }

        table.summary td.summary-value {
            font-weight: 600;
            color: #09090b;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e4e4e7;
            border-radius: 8px;
        }

        table.data thead th {
            background: #f4f4f5;
            border-bottom: 1px solid #e4e4e7;
            padding: 7px 10px;
            font-size: 8px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #71717a;
            text-align: left;
        }

        table.data tbody td {
            border-bottom: 1px solid #e4e4e7;
            padding: 7px 10px;
            font-size: 10px;
            color: #27272a;
        }

        table.data tbody tr:last-child td {
            border-bottom: none;
        }

        table.data td.name,
        table.data th.name {
            font-weight: 500;
            color: #09090b;
        }

        .num {
            text-align: right;
        }

        table.data tfoot td {
            border-top: 1px solid #e4e4e7;
            padding: 7px 10px;
            font-size: 10px;
            font-weight: 600;
            color: #09090b;
        }

        .empty {
            border: 1px solid #e4e4e7;
            border-radius: 8px;
            padding: 14px;
            text-align: center;
            font-size: 10px;
            color: #a1a1aa;
        }
    </style>
</head>
<body>
    <header>
        <div class="tenant">{{ $tenantName }}</div>
        <div class="title">@yield('title')</div>
        <div class="meta">{{ $periodLabel }} &nbsp;·&nbsp; Gerado em {{ $generatedAt }}</div>
        <div class="rule"></div>
    </header>

    <footer>
        <span class="brand">{{ config('app.name') }}</span>
        <span class="pages"></span>
    </footer>

    <main>
        @yield('content')
    </main>
</body>
</html>
