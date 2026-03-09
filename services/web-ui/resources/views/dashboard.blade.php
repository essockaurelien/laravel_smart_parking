<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sPark Control Deck</title>
    <style>
        :root {
            --ink: #0b132b;
            --sand: #f8f1e8;
            --mint: #c1fba4;
            --orange: #f4a261;
            --blue: #3a86ff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Palatino Linotype", "Book Antiqua", Palatino, serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 15% 20%, #ffd16640, transparent 55%),
                radial-gradient(circle at 85% 10%, #a8dadc66, transparent 50%),
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
        h1 { margin: 0; font-size: 28px; letter-spacing: 2px; }
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
        .note { font-size: 13px; opacity: 0.8; }
        .status { font-weight: 700; color: #1b4332; }
        .error { color: #9b2226; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid var(--ink); padding: 6px; text-align: left; }
    </style>
</head>
<body>
    <header>
        <h1>sPark Control Deck</h1>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit" style="background: var(--orange);">Logout</button>
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
                <h2>Premium Reservation</h2>
                <form method="POST" action="/reservations">
                    @csrf
                    <label>Spot ID</label>
                    <input type="number" name="spot_id" required>
                    <label>Arrival ETA</label>
                    <input type="datetime-local" name="arrival_eta" required>
                    <label>Duration (minutes)</label>
                    <input type="number" name="duration_minutes" min="1" required>
                    <button type="submit">Reserve Spot</button>
                </form>
            </div>
            <div class="card">
                <h2>Charging Request</h2>
                <form method="POST" action="/charge-requests">
                    @csrf
                    <label>Spot ID</label>
                    <input type="number" name="spot_id" required>
                    <label>Current %</label>
                    <input type="number" name="current_percent" min="0" max="100" required>
                    <label>Target %</label>
                    <input type="number" name="target_percent" min="1" max="100" required>
                    <button type="submit">Queue Charging</button>
                </form>
            </div>
            <div class="card">
                <h2>Admin Pricing</h2>
                <form method="POST" action="/pricing">
                    @csrf
                    <label>Parking Rate (per hour)</label>
                    <input type="number" step="0.01" name="parking_rate_per_hour" required>
                    <label>Charging Cost (per kW)</label>
                    <input type="number" step="0.01" name="charging_cost_per_kw" required>
                    <button type="submit">Update Pricing</button>
                </form>
                @if ($pricing)
                    <p class="note">Current: {{ $pricing['parking_rate_per_hour'] ?? '' }} / {{ $pricing['charging_cost_per_kw'] ?? '' }}</p>
                @endif
            </div>
        </div>

        <div class="card">
            <h2>Spot Overview</h2>
            @if (count($spots))
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Occupied</th>
                            <th>Reserved</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($spots as $spot)
                            <tr>
                                <td>{{ $spot['code'] }}</td>
                                <td>{{ $spot['is_occupied'] ? 'Yes' : 'No' }}</td>
                                <td>{{ $spot['is_reserved'] ? 'Yes' : 'No' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="note">No spot data yet. Seed the parking service.</p>
            @endif
        </div>

        <div class="card">
            <h2>Charging Queue</h2>
            @if (count($requests))
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Spot</th>
                            <th>Target</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $request)
                            <tr>
                                <td>{{ $request['id'] }}</td>
                                <td>{{ $request['spot_id'] }}</td>
                                <td>{{ $request['target_percent'] }}%</td>
                                <td>{{ $request['status'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="note">No charge requests yet.</p>
            @endif
        </div>
    </main>
</body>
</html>
