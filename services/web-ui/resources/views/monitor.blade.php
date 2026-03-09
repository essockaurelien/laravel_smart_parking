<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sPark - Monitor Ingresso</title>
    <style>
        :root {
            --ink: #101828;
            --sky: #e0f2fe;
            --sun: #fef3c7;
            --green: #16a34a;
            --red: #dc2626;
            --blue: #1d4ed8;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Palatino Linotype", "Book Antiqua", Palatino, serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 15% 15%, #a5f3fc66, transparent 60%),
                radial-gradient(circle at 85% 10%, #fde68a66, transparent 55%),
                #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .panel {
            width: min(960px, 92vw);
            background: #ffffff;
            border: 3px solid var(--ink);
            box-shadow: 10px 10px 0 var(--ink);
            padding: 32px;
            animation: slideIn 0.6s ease-out;
        }
        h1 {
            margin: 0 0 12px;
            font-size: 34px;
            letter-spacing: 1px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 18px;
        }
        .stat {
            border: 2px solid var(--ink);
            padding: 18px;
            background: #f9fafb;
        }
        .stat h2 { margin: 0 0 6px; font-size: 20px; }
        .value { font-size: 34px; font-weight: 700; }
        .ok { color: var(--green); }
        .warn { color: var(--red); }
        .note { margin-top: 14px; font-size: 14px; opacity: 0.8; }
        .spots {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
        }
        .spot {
            border: 2px solid var(--ink);
            padding: 10px;
            background: var(--sky);
        }
        .spot.occupied { background: #fecaca; }
        .spot.reserved { background: var(--sun); }
        .legend {
            margin-top: 12px;
            display: flex;
            gap: 12px;
            font-size: 13px;
        }
        .legend span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .dot {
            width: 12px;
            height: 12px;
            border: 2px solid var(--ink);
            background: var(--sky);
        }
        .dot.occupied { background: #fecaca; }
        .dot.reserved { background: var(--sun); }
        @keyframes slideIn {
            from { transform: translateY(12px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="panel">
        <h1>Monitor Ingresso sPark</h1>
        @if ($error)
            <p class="note warn">{{ $error }}</p>
        @else
            <div class="grid">
                <div class="stat">
                    <h2>Posti Totali</h2>
                    <div class="value">{{ $summary['total_spots'] ?? 0 }}</div>
                </div>
                <div class="stat">
                    <h2>Disponibili</h2>
                    <div class="value ok">{{ $summary['available_spots'] ?? 0 }}</div>
                </div>
                <div class="stat">
                    <h2>Occupati</h2>
                    <div class="value warn">{{ $summary['occupied_spots'] ?? 0 }}</div>
                </div>
                <div class="stat">
                    <h2>Prenotati</h2>
                    <div class="value">{{ $summary['reserved_spots'] ?? 0 }}</div>
                </div>
            </div>

            @if (!empty($summary['spots']))
                <div class="spots">
                    @foreach ($summary['spots'] as $spot)
                        <div class="spot {{ $spot['is_occupied'] ? 'occupied' : ($spot['is_reserved'] ? 'reserved' : '') }}">
                            <strong>{{ $spot['code'] }}</strong>
                            <div class="note">{{ $spot['is_occupied'] ? 'Occupato' : ($spot['is_reserved'] ? 'Prenotato' : 'Libero') }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="legend">
                    <span><span class="dot"></span> Libero</span>
                    <span><span class="dot reserved"></span> Prenotato</span>
                    <span><span class="dot occupied"></span> Occupato</span>
                </div>
            @endif
        @endif
        <p class="note">Aggiornare la pagina per ricaricare i dati.</p>
    </div>
</body>
</html>
