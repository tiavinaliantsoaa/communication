<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion - ESCM Plateformes</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-escm-sidebar min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8 sm:p-10">
            {{-- Logo --}}
            <div class="text-center mb-8">
                <img src="{{ asset('images/escm-logo.png') }}" alt="" class="mx-auto h-16 w-16 object-contain mb-4">
                <h1 class="text-2xl font-bold text-slate-900">ESCM Plateformes</h1>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Mot de passe</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary">
                        <span class="text-sm text-slate-600">Se souvenir de moi</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm text-escm-primary hover:text-escm-primary-dark font-medium">Mot de passe oublié ?</a>
                    @endif
                </div>

                <button type="submit" class="w-full bg-escm-primary hover:bg-escm-primary-dark text-white font-semibold py-2.5 px-4 rounded-lg transition-colors shadow-sm">
                    Connexion
                </button>
            </form>
        </div>
        <p class="text-center text-white/80 text-xs mt-6">&copy; {{ date('Y') }} ESCM Business School</p>
    </div>
</body>
</html>
