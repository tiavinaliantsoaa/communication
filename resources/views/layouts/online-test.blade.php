<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Test en ligne' }} — ESCM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-sans antialiased bg-slate-100 text-slate-900">
    <div class="min-h-screen">
        @if (session('success'))
            <div class="mx-auto max-w-3xl px-4 pt-4">
                <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="mx-auto max-w-3xl px-4 pt-4">
                <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            </div>
        @endif
        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
