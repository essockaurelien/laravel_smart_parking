<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sPark Accesso</title>
    <style>
        :root {
            --bg: #f6f0e9;
            --ink: #1f1b16;
            --accent: #006d77;
            --accent-2: #e29578;
        }
        body {
            margin: 0;
            font-family: "Palatino Linotype", "Book Antiqua", Palatino, serif;
            color: var(--ink);
            background: radial-gradient(circle at 20% 20%, #fef3c7, transparent 45%),
                        radial-gradient(circle at 80% 10%, #cde7e4, transparent 40%),
                        var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: #ffffff;
            border: 2px solid var(--ink);
            padding: 28px;
            width: 360px;
            box-shadow: 8px 8px 0 var(--ink);
        }
        h1 { margin-top: 0; font-size: 28px; letter-spacing: 1px; }
        label { display: block; margin: 12px 0 6px; font-weight: 600; }
        input {
            width: 100%;
            padding: 10px;
            border: 2px solid var(--ink);
            background: #fffaf3;
        }
        button {
            width: 100%;
            margin-top: 16px;
            padding: 12px;
            border: 2px solid var(--ink);
            background: var(--accent);
            color: white;
            font-weight: 700;
            cursor: pointer;
        }
        .link { margin-top: 12px; text-align: center; }
        .error { color: #9b2226; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Accesso sPark</h1>
        @if ($errors->any())
            <p class="error">{{ $errors->first() }}</p>
        @endif
        <form method="POST" action="/login">
            @csrf
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit">Entra</button>
        </form>
        <div class="link">
            <a href="/register">Crea un account</a>
        </div>
    </div>
</body>
</html>
