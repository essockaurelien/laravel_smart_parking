<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sPark Registrazione</title>
    <style>
        :root {
            --bg: #f1efe6;
            --ink: #1f1b16;
            --accent: #8d5a2b;
            --accent-2: #d4a373;
        }
        body {
            margin: 0;
            font-family: "Palatino Linotype", "Book Antiqua", Palatino, serif;
            color: var(--ink);
            background: linear-gradient(140deg, #f8f4ef 0%, #e9edc9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: #ffffff;
            border: 2px solid var(--ink);
            padding: 28px;
            width: 380px;
            box-shadow: -8px 8px 0 var(--ink);
        }
        h1 { margin-top: 0; font-size: 28px; }
        label { display: block; margin: 12px 0 6px; font-weight: 600; }
        input, select {
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
        <h1>Crea Account</h1>
        @if ($errors->any())
            <p class="error">{{ $errors->first() }}</p>
        @endif
        <form method="POST" action="/register">
            @csrf
            <label>Nome</label>
            <input type="text" name="name" value="{{ old('name') }}" required>
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <label>Tipo Account</label>
            <select name="role" required>
                <option value="base">Base</option>
                <option value="premium">Premium</option>
            </select>
            <button type="submit">Registrati</button>
        </form>
        <div class="link">
            <a href="/login">Torna al login</a>
        </div>
    </div>
</body>
</html>
