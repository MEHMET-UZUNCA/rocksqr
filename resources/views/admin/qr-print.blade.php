<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #1a1a2e;
            background: #f5f5f5;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 16px 20px;
            background: #1a1a2e;
            color: #fff;
        }

        .toolbar h1 {
            margin: 0;
            font-size: 22px;
        }

        .toolbar p {
            margin: 6px 0 0;
            color: #d1d5db;
            font-size: 13px;
        }

        .toolbar button {
            border: 0;
            background: #d4af37;
            color: #1a1a2e;
            font-weight: 700;
            padding: 12px 18px;
            border-radius: 10px;
            cursor: pointer;
        }

        .page {
            width: 190mm;
            min-height: 277mm;
            margin: 12px auto;
            background: #fff;
            padding: 8mm;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-auto-rows: 1fr;
            gap: 8mm;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .card {
            border: 1px solid #d1d5db;
            border-radius: 18px;
            padding: 8mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            break-inside: avoid;
        }

        .qr-box {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 6mm;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 72mm;
            background: #fffdf7;
        }

        .qr-box img {
            width: 100%;
            height: auto;
            max-width: 72mm;
        }

        .meta {
            text-align: center;
            margin-top: 6mm;
        }

        .meta .brand {
            font-size: 10px;
            letter-spacing: 0.3em;
            color: #6b7280;
            text-transform: uppercase;
        }

        .meta .table {
            margin-top: 3mm;
            font-size: 22px;
            font-weight: 700;
        }

        .meta .caption {
            margin-top: 2mm;
            font-size: 12px;
            color: #4b5563;
        }

        .meta .url {
            margin-top: 3mm;
            font-size: 9px;
            color: #9ca3af;
            word-break: break-all;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .page {
                margin: 0;
                width: auto;
                min-height: auto;
                box-shadow: none;
                page-break-after: always;
            }

            .page:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>{{ $title }}</h1>
            <p>{{ $subtitle }}</p>
        </div>
        <button type="button" onclick="window.print()">Yazdır</button>
    </div>

    @foreach(collect($generatedQrs)->chunk(8) as $page)
        <section class="page">
            @foreach($page as $qr)
                <article class="card">
                    <div class="qr-box">
                        <img src="{{ $qr['image_data_uri'] }}" alt="Masa {{ $qr['table_no'] }} QR">
                    </div>
                    <div class="meta">
                        <div class="brand">Rocks Hotel</div>
                        <div class="table">Masa {{ $qr['table_no'] }}</div>
                        <div class="caption">Menü için QR kodu okutun</div>
                        <div class="url">{{ $qr['url'] }}</div>
                    </div>
                </article>
            @endforeach
        </section>
    @endforeach
</body>
</html>