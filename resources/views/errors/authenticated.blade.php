@extends('layouts.app')

@section('content')
<div class="flex min-h-[60vh] items-center justify-center">
    <div class="w-full max-w-lg text-center rounded-2xl border border-slate-200 bg-white px-8 py-12 shadow-sm">
        <p class="text-6xl font-bold tracking-tight text-escm-primary leading-none">{{ $code }}</p>
        <h2 class="mt-4 text-xl font-semibold text-slate-900">{{ $heading }}</h2>
        <p class="mt-3 text-sm text-slate-500">{{ $message }}</p>
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center justify-center rounded-lg bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-5 py-2.5 w-full sm:w-auto">
                Tableau de bord
            </a>
            <button type="button" onclick="history.back()"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-medium px-5 py-2.5 w-full sm:w-auto">
                Page précédente
            </button>
        </div>
    </div>
</div>
@endsection
