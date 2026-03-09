<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sPark - Admin</title>
    <style>
        :root {
            --ink: #1f2937;
            --bg: #f8f7f3;
            --blue: #2563eb;
            --red: #dc2626;
            --emerald: #10b981;
            --amber: #f59e0b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Palatino Linotype", "Book Antiqua", Palatino, serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 10% 10%, #bae6fd55, transparent 55%),
                radial-gradient(circle at 90% 20%, #fecdd355, transparent 50%),
                var(--bg);
            min-height: 100vh;
        }
        header {
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--ink);
            background: #ffffffd9;
            position: sticky;
            top: 0;
            backdrop-filter: blur(6px);
        }
        h1 { margin: 0; font-size: 26px; letter-spacing: 1px; }
        main { padding: 28px; display: grid; gap: 24px; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .card {
            background: #fff;
            border: 2px solid var(--ink);
            padding: 18px;
            box-shadow: 6px 6px 0 var(--ink);
        }
        .card h2 { margin-top: 0; font-size: 20px; }
        label { display: block; margin: 10px 0 6px; font-weight: 600; }
        input, select {
            width: 100%;
            padding: 8px 10px;
            border: 2px solid var(--ink);
            background: #fffaf3;
        }
        button {
            margin-top: 12px;
            padding: 10px 14px;
            border: 2px solid var(--ink);
            background: var(--blue);
            color: white;
            font-weight: 700;
            cursor: pointer;
        }
        .secondary { background: var(--amber); }
        .note { font-size: 13px; opacity: 0.8; }
        .status { font-weight: 700; color: var(--emerald); }
        .error { color: var(--red); font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid var(--ink); padding: 6px; text-align: left; }
        .lights { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; }
        .light {
            border: 2px solid var(--ink);
            padding: 10px;
            background: #f9fafb;
        }
        .light.on { background: #fef3c7; }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>sPark Admin</h1>
            <div class="note">Benvenuto, {{ $user['name'] ?? 'Admin' }}</div>
        </div>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit" class="secondary">Esci</button>
        </form>
    </header>
    <main>
        @if (session('status'))
            <div class="card"><span class="status">{{ session('status') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="card"><span class="error">{{ $errors->first() }}</span></div>
        @endif

        <div class="grid">
            <div class="card">
                <h2>Tariffe</h2>
                <form method="POST" action="/pricing">
                    @csrf
                    <label>Costo sosta (ora)</label>
                    <input type="number" step="0.01" name="parking_rate_per_hour" required>
                    <label>Costo ricarica (kW)</label>
                    <input type="number" step="0.01" name="charging_cost_per_kw" required>
                    <button type="submit">Aggiorna</button>
                </form>
                @if ($pricing)
                    <p class="note">Attuali: {{ $pricing['parking_rate_per_hour'] ?? '' }} / {{ $pricing['charging_cost_per_kw'] ?? '' }}</p>
                @endif
            </div>

            <div class="card">
                <h2>Filtro Pagamenti</h2>
                <form method="GET" action="/">
                    <label>Tipo</label>
                    <select name="payment_type">
                        <option value="">Tutti</option>
                        <option value="parking">Sosta</option>
                        <option value="charging">Ricarica</option>
                        <option value="no_show">No Show</option>
                    </select>
                    <label>Ruolo</label>
                    <select name="payment_role">
                        <option value="">Tutti</option>
                        <option value="base">Base</option>
                        <option value="premium">Premium</option>
                        <option value="admin">Admin</option>
                    </select>
                    <label>Da</label>
                    <input type="datetime-local" name="payment_from">
                    <label>A</label>
                    <input type="datetime-local" name="payment_to">
                    <button type="submit">Applica</button>
                </form>
            </div>

            <div class="card">
                <h2>Stato Posti</h2>
                @if (count($spots))
                    @php
                        $occupiedCount = collect($spots)->where('is_occupied', true)->count();
                        $reservedCount = collect($spots)->where('is_reserved', true)->count();
                        $totalCount = count($spots);
                    @endphp
                    <p class="note">Totali: {{ $totalCount }} | Liberi: {{ max(0, $totalCount - $occupiedCount - $reservedCount) }} | Occupati: {{ $occupiedCount }} | Prenotati: {{ $reservedCount }}</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Codice</th>
                                <th>Occupato</th>
                                <th>Prenotato</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($spots as $spot)
                                <tr>
                                    <td>{{ $spot['code'] }}</td>
                                    <td>{{ $spot['is_occupied'] ? 'Si' : 'No' }}</td>
                                    <td>{{ $spot['is_reserved'] ? 'Si' : 'No' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="note">Nessun dato sui posti.</p>
                @endif
            </div>
        </div>

        <div class="card">
            <h2>Coda Ricariche</h2>
            @if (count($requests))
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Spot</th>
                            <th>Target</th>
                            <th>Stato</th>
                            <th>Coda</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $request)
                            <tr>
                                <td>{{ $request['id'] }}</td>
                                <td>{{ $request['spot_id'] }}</td>
                                <td>{{ $request['target_percent'] }}%</td>
                                <td>{{ $request['status'] }}</td>
                                <td>{{ $request['queue_position'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="note">Nessuna richiesta di ricarica.</p>
            @endif
        </div>

        <div class="card">
            <h2>Pagamenti</h2>
            @if (count($payments))
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utente</th>
                            <th>Ruolo</th>
                            <th>Tipo</th>
                            <th>Importo</th>
                            <th>Stato</th>
                            <th>Pagato il</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td>{{ $payment['id'] }}</td>
                                <td>{{ $payment['user_id'] }}</td>
                                <td>{{ $payment['user_role'] ?? '-' }}</td>
                                <td>{{ $payment['type'] }}</td>
                                <td>{{ $payment['amount'] }}</td>
                                <td>{{ $payment['status'] }}</td>
                                <td>{{ $payment['paid_at'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="note">Nessun pagamento per il filtro selezionato.</p>
            @endif
        </div>

        <div class="card">
            <h2>Lampadine Hue Emulator</h2>
            @if (is_array($hue_lights) && count($hue_lights))
                <div class="lights">
                    @foreach ($hue_lights as $lightId => $light)
                        <div class="light {{ ($light['state']['on'] ?? false) ? 'on' : '' }}">
                            <strong>Light {{ $lightId }}</strong>
                            <div class="note">{{ $light['name'] ?? 'Spot' }}</div>
                            <div class="note">Accesa: {{ ($light['state']['on'] ?? false) ? 'Si' : 'No' }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="note">Nessuna lampadina rilevata. Controlla diyHue.</p>
            @endif
        </div>
    </main>
</body>
</html>
