@props([
    'name',
    'value' => null,
    'required' => false,
    'id' => null,
])

@php
    $raw = old($name, $value);
    if ($raw instanceof \Carbon\CarbonInterface) {
        $iso = $raw->format('Y-m-d');
    } elseif (is_string($raw) && preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', trim($raw))) {
        [$d, $m, $y] = array_map('intval', explode('/', trim($raw)));
        $iso = sprintf('%04d-%02d-%02d', $y, $m, $d);
    } elseif (is_string($raw) && preg_match('/^\d{4}-\d{2}-\d{2}/', $raw)) {
        $iso = substr($raw, 0, 10);
    } else {
        $iso = '';
    }
    $display = $iso !== '' ? \Carbon\Carbon::parse($iso)->format('d/m/Y') : '';
    $inputId = $id ?? $name;
@endphp

<div
    x-data="{
        iso: @js($iso),
        display: @js($display),
        toIso(value) {
            const m = String(value || '').trim().match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
            if (!m) return '';
            return m[3] + '-' + m[2].padStart(2, '0') + '-' + m[1].padStart(2, '0');
        },
        toDisplay(value) {
            if (!value) return '';
            const p = String(value).substring(0, 10).split('-');
            if (p.length !== 3) return '';
            return p[2] + '/' + p[1] + '/' + p[0];
        },
        commit() {
            const next = this.toIso(this.display);
            this.iso = next;
            if (next) this.display = this.toDisplay(next);
        }
    }"
>
    <input
        type="text"
        id="{{ $inputId }}"
        inputmode="numeric"
        placeholder="JJ/MM/AAAA"
        autocomplete="off"
        x-model="display"
        @input="iso = toIso(display) || iso"
        @blur="commit()"
        @change="commit()"
        @if($required) required @endif
        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"
    >
    <input type="hidden" name="{{ $name }}" x-bind:value="iso">
</div>
