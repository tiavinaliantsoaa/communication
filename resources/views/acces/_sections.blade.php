@php
    $selectedKeys = array_values($selectedKeys ?? []);
@endphp

<div class="divide-y divide-slate-100">
    @foreach($permissionGroups as $group => $perms)
        @php
            $keys = $perms->pluck('key')->sort()->values()->all();
            $readKeys = $perms->filter(fn ($perm) => str_ends_with($perm->key, '.view'))->pluck('key')->sort()->values()->all();
            $chosen = collect($selectedKeys)->intersect($keys)->sort()->values()->all();
            $mode = 'none';
            if ($chosen !== [] && $chosen === $keys) {
                $mode = 'write';
            } elseif ($readKeys !== [] && $chosen === $readKeys) {
                $mode = 'read';
            } elseif ($chosen !== []) {
                $mode = 'custom';
            }
        @endphp
        <div class="px-5 py-4" data-section>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $group }}</p>
                <label class="flex items-center gap-2 text-xs font-medium text-slate-600">
                    Accès
                    <select class="section-mode rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary" data-read='@json($readKeys)' data-write='@json($keys)'>
                        <option value="none" @selected($mode === 'none')>Aucun accès</option>
                        <option value="read" @selected($mode === 'read')>Lecture seule</option>
                        <option value="write" @selected($mode === 'write')>Lecture et écriture</option>
                        <option value="custom" @selected($mode === 'custom') @if($mode !== 'custom') hidden @endif>Personnalisé</option>
                    </select>
                </label>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                @foreach($perms as $perm)
                    <label class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-50 cursor-pointer text-sm text-slate-700">
                        <input
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $perm->key }}"
                            class="perm-check rounded border-slate-300 text-escm-primary focus:ring-escm-primary"
                            @checked(in_array($perm->key, $selectedKeys, true))
                        >
                        {{ $perm->label }}
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('.section-mode').forEach((select) => {
                const section = select.closest('[data-section]');
                const read = JSON.parse(select.dataset.read || '[]');
                const write = JSON.parse(select.dataset.write || '[]');
                const boxes = () => [...section.querySelectorAll('.perm-check')];
                const apply = (mode) => {
                    boxes().forEach((box) => {
                        if (mode === 'read') box.checked = read.includes(box.value);
                        else if (mode === 'write') box.checked = write.includes(box.value);
                        else if (mode === 'none') box.checked = false;
                    });
                };
                select.addEventListener('change', () => apply(select.value));
                boxes().forEach((box) => box.addEventListener('change', () => {
                    const checked = boxes().filter((item) => item.checked).map((item) => item.value).sort();
                    const same = (list) => list.length === checked.length && [...list].sort().every((key, index) => key === checked[index]);
                    let mode = 'custom';
                    if (checked.length === 0) mode = 'none';
                    else if (same(read)) mode = 'read';
                    else if (same(write)) mode = 'write';
                    const custom = [...select.options].find((option) => option.value === 'custom');
                    if (custom) custom.hidden = mode !== 'custom';
                    select.value = mode;
                }));
            });
        </script>
    @endpush
@endonce
