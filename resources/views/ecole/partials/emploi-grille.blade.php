<div class="overflow-x-auto">
    <div style="min-width: 920px;">
        <div style="display:grid; grid-template-columns: 72px repeat(5, minmax(0, 1fr));">
            <div class="px-2 py-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400 leading-tight">
                Semaine
                <div class="text-sm font-bold text-slate-600">{{ $weekNumber }}</div>
            </div>
            @foreach($columns as $column)
                <div class="px-3 py-2 text-white" style="background:#1f2937;">
                    <div class="text-[11px] font-medium uppercase tracking-wider" style="color:#cbd5e1;">{{ $column['label'] }}</div>
                    <div class="text-xl font-bold leading-none mt-0.5">{{ $column['number'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="relative" style="height: 576px;">
            <div style="display:grid; grid-template-columns: 72px repeat(5, minmax(0, 1fr)); height: 100%;">
                <div class="relative border-r border-slate-200 bg-white">
                    @foreach($hours as $hour)
                        @php
                            $shift = $loop->first ? '0' : ($loop->last ? '-100%' : '-50%');
                        @endphp
                        <span class="absolute right-2 text-[11px] font-medium text-slate-400" style="top: {{ $hour['top'] }}%; transform: translateY({{ $shift }});">{{ $hour['label'] }}</span>
                    @endforeach
                </div>

                @foreach($columns as $column)
                    <div class="relative border-l border-slate-100" style="{{ $column['isToday'] ? 'background:#fffbeb;' : 'background:#fff;' }}">
                        @foreach($hours as $hour)
                            @if(! $loop->first)
                                <div class="absolute left-0 right-0" style="top: {{ $hour['top'] }}%; border-top: 1px dashed #e2e8f0;"></div>
                            @endif
                        @endforeach
                        @foreach($column['events'] as $event)
                            @php
                                $top = number_format($event['top'], 2, '.', '');
                                $height = number_format($event['height'], 2, '.', '');
                                $left = number_format($event['left'], 2, '.', '');
                                $width = number_format($event['width'], 2, '.', '');
                            @endphp
                            <div class="absolute overflow-hidden rounded-md px-2 py-1" style="top: {{ $top }}%; height: {{ $height }}%; left: calc({{ $left }}% + 4px); width: calc({{ $width }}% - 8px); background:#dbeafe; border:1px solid #93c5fd; z-index: 2;" title="{{ $event['matiere'] }}{{ $event['professeur'] ? ' — '.$event['professeur'] : '' }}{{ $event['salle'] ? ' — '.$event['salle'] : '' }}">
                                <p class="text-[11px] font-bold leading-tight truncate" style="color:#1e3a8a;">{{ $event['matiere'] }}</p>
                                @if($event['professeur'])
                                    <p class="text-[10px] leading-tight truncate" style="color:#1e3a8a;">{{ $event['professeur'] }}</p>
                                @endif
                                @if($event['salle'])
                                    <p class="text-[10px] leading-tight truncate" style="color:#475569;">{{ $event['salle'] }}</p>
                                @endif
                                <p class="text-[10px] leading-tight truncate" style="color:#64748b;">{{ $event['horaire'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            @if($nowTop !== null)
                <div class="absolute" style="top: {{ number_format($nowTop, 2, '.', '') }}%; left: 72px; right: 0; height: 2px; background:#2563eb; z-index: 4;">
                    <span class="absolute rounded-full" style="left: -5px; top: -4px; width: 10px; height: 10px; background:#2563eb;"></span>
                </div>
            @endif
        </div>
    </div>
</div>
