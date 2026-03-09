<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sPark - Base</title>
    <style>
        :root {
            --ink: #14213d;
            --sand: #f6f1e9;
            --blue: #1d4ed8;
            --orange: #f59e0b;
            --mint: #10b981;
            --rose: #ef4444;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Palatino Linotype", "Book Antiqua", Palatino, serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 15% 15%, #fde68a55, transparent 55%),
                radial-gradient(circle at 85% 20%, #93c5fd55, transparent 50%),
                var(--sand);
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
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
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
        .secondary { background: var(--orange); }
        .note { font-size: 13px; opacity: 0.8; }
        .status { font-weight: 700; color: var(--mint); }
        .error { color: var(--rose); font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid var(--ink); padding: 6px; text-align: left; }
        .tag {
            display: inline-block;
            padding: 4px 8px;
            border: 2px solid var(--ink);
            background: #e0f2fe;
            font-weight: 700;
        }
        .inline { display: inline-flex; gap: 8px; align-items: center; }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>sPark Base</h1>
            <div class="note">Benvenuto, {{ $user['name'] ?? 'Automobilista' }}</div>
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

        @if (!empty($charge_quote))
            <div class="card">
                <h2>Preventivo Ricarica</h2>
                <p class="note">Posizione in coda: <strong>{{ $charge_quote['queue_position'] ?? '-' }}</strong></p>
                <p class="note">Auto prima di te: <strong>{{ $charge_quote['cars_before'] ?? '-' }}</strong></p>
                <p class="note">Minuti stimati: <strong>{{ $charge_quote['estimated_minutes'] ?? '-' }}</strong></p>
            </div>
        @endif

        <div class="grid">
            <div class="card">
                <h2>Richiesta Ricarica</h2>
                <form method="POST" action="/charge-quotes">
                    @csrf
                    <label>ID Posto</label>
                    <input type="number" name="spot_id" required>
                    <label>Percentuale attuale</label>
                    <input type="number" name="current_percent" min="0" max="100" required>
                    <label>Percentuale desiderata</label>
                    <input type="number" name="target_percent" min="1" max="100" required>
                    <label>Batteria kWh (opzionale)</label>
                    <input type="number" name="battery_kwh" min="1" step="0.1">
                    <label class="inline"><input type="checkbox" name="notify_on_complete" value="1"> Avvisami al completamento</label>
                    <button type="submit">Calcola Preventivo</button>
                </form>
                <form method="POST" action="/charge-requests">
                    @csrf
                    <input type="hidden" name="spot_id" value="{{ $charge_draft['spot_id'] ?? '' }}">
                    <input type="hidden" name="current_percent" value="{{ $charge_draft['current_percent'] ?? '' }}">
                    <input type="hidden" name="target_percent" value="{{ $charge_draft['target_percent'] ?? '' }}">
                    <input type="hidden" name="battery_kwh" value="{{ $charge_draft['battery_kwh'] ?? '' }}">
                    <input type="hidden" name="notify_on_complete" value="{{ $charge_draft['notify_on_complete'] ?? 0 }}">
                    <button type="submit" class="secondary">Accetta e Metti in Coda</button>
                </form>
                <p class="note">Puoi richiedere un'altra ricarica dopo il completamento.</p>
            </div>

            <div class="card">
                <h2>Check-in / Check-out</h2>
                <form method="POST" action="/checkin">
                    @csrf
                    <label>ID Posto</label>
                    <input type="number" name="spot_id" required>
                    <button type="submit">Effettua Check-in</button>
                </form>
                <form method="POST" action="/checkout">
                    @csrf
                    <label>ID Sessione</label>
                    <input type="number" name="session_id" required>
                    <button type="submit" class="secondary">Effettua Check-out</button>
                </form>
                <p class="note">Usa l'ID sessione dalle sessioni aperte.</p>
            </div>

            <div class="card">
                <h2>Stato Posti</h2>
                @if (count($spots))
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
            <h2>Le tue Sessioni</h2>
            @if (count($sessions))
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Posto</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Minuti</th>
                            <th>Totale</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessions as $session)
                            <tr>
                                <td>{{ $session['id'] }}</td>
                                <td>{{ $session['spot_id'] }}</td>
                                <td>{{ $session['check_in_at'] }}</td>
                                <td>{{ $session['check_out_at'] ?? 'Aperta' }}</td>
                                <td>{{ $session['total_minutes'] }}</td>
                                <td>{{ $session['parking_fee'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="note">Nessuna sessione trovata.</p>
            @endif
        </div>

        <div class="card">
            <h2>Pagamenti</h2>
            @if (count($payments))
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Importo</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td>{{ $payment['id'] }}</td>
                                <td>{{ $payment['type'] }}</td>
                                <td>{{ $payment['amount'] }}</td>
                                <td>{{ $payment['paid_at'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="note">Nessun pagamento disponibile.</p>
            @endif
        </div>

        <div class="card">
            <h2>Coda Ricariche</h2>
            @if (count($requests))
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Posto</th>
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
    </main>
</body>
</html>
