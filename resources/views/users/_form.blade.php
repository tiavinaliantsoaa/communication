@php
    $isEdit = $edit ?? false;
@endphp

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nom complet</label>
    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required
           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Mot de passe {{ $isEdit ? '(laisser vide pour ne pas changer)' : '' }}</label>
    <input type="password" name="password" {{ $isEdit ? '' : 'required' }}
           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
    @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirmation mot de passe</label>
    <input type="password" name="password_confirmation" {{ $isEdit ? '' : 'required' }}
           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Rôle</label>
    <select name="role" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
        @foreach($roles as $value => $label)
            <option value="{{ $value }}" @selected(old('role', $user->role ?? '') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Département</label>
    <select name="departement_id" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
        <option value="">Choisir un département</option>
        @foreach($departements as $departement)
            <option value="{{ $departement->id }}" @selected((string) old('departement_id', $user->departement_id ?? '') === (string) $departement->id)>{{ $departement->nom }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-slate-500">Cet utilisateur ne verra que les données de ce département. Les menus restent les mêmes.</p>
    @error('departement_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
