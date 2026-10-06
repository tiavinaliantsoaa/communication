<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }} — ESCM Communication</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-slate-900 via-slate-800 to-blue-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-lg text-center">
        <div class="bg-white rounded-2xl shadow-2xl px-8 py-12 sm:px-12">
            <p class="text-7xl sm:text-8xl font-bold tracking-tight text-escm-primary leading-none">{{ $code }}</p>
            <h1 class="mt-4 text-xl sm:text-2xl font-semibold text-slate-900">{{ $heading }}</h1>
            <p class="mt-3 text-sm sm:text-base text-slate-500 max-w-sm mx-auto">{{ $message }}</p>
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center rounded-lg bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-5 py-2.5 transition-colors w-full sm:w-auto">
                    Se connecter
                </a>
                <button type="button" onclick="history.back()"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-medium px-5 py-2.5 transition-colors w-full sm:w-auto">
                    Page précédente
                </button>
            </div>
        </div>
        <p class="mt-6 text-xs text-slate-400 tracking-wide uppercase">ESCM Communication</p>
    </div>
</body>
</html>
