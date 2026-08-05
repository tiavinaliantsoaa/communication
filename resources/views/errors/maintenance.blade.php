<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    @if (isset($retryAfter))
        <meta http-equiv="Refresh" content="{{ $retryAfter }}">
    @endif
    <title>Maintenance en cours — {{ config('app.name', 'ESCM Communication') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-1: #0f172a;
            --bg-2: #1e3a5f;
            --accent: #2563eb;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "DM Sans", system-ui, sans-serif;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%, rgba(37, 99, 235, 0.35), transparent 55%),
                radial-gradient(ellipse 70% 50% at 90% 90%, rgba(14, 165, 233, 0.2), transparent 50%),
                linear-gradient(145deg, var(--bg-1), var(--bg-2));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: var(--text);
        }
        .card {
            width: 100%;
            max-width: 28rem;
            background: var(--card);
            border-radius: 1.25rem;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
        }
        .icon {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1.25rem;
            border-radius: 9999px;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
        }
        .icon svg { width: 1.75rem; height: 1.75rem; }
        h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        p {
            margin: 0.85rem 0 0;
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.55;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 1.5rem;
            padding: 0.4rem 0.85rem;
            border-radius: 9999px;
            background: #f1f5f9;
            color: #334155;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .dot {
            width: 0.45rem;
            height: 0.45rem;
            border-radius: 9999px;
            background: #f59e0b;
            animation: pulse 1.4s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.45; transform: scale(0.85); }
        }
        .brand {
            margin-top: 1.75rem;
            font-size: 0.8rem;
            color: #94a3b8;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon" aria-hidden="true">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11.42 15.17l-5.13 5.12a2.12 2.12 0 01-3-3l5.12-5.13M14.5 7.5l2 2M8 16l2.5-2.5m5.5-7.5a2.828 2.828 0 114 4L12 15l-4 1 1-4 7.5-7.5z"/>
            </svg>
        </div>
        <h1>Maintenance en cours</h1>
        <p>
            L’outil ESCM Communication est temporairement indisponible.
            Nous effectuons une mise à jour. Merci de réessayer dans quelques instants.
        </p>
        <div class="badge">
            <span class="dot"></span>
            Travaux en cours
        </div>
        <div class="brand">ESCM Communication</div>
    </div>
</body>
</html>
