<?php

namespace App\Support;

use App\Models\EcoleSeance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EcoleTimetable
{
    public const START = 8;

    public const END = 17;

    /**
     * @return array<int, string>
     */
    public static function days(): array
    {
        return [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
        ];
    }

    /**
     * @return list<array{label: string, top: float}>
     */
    public static function hours(): array
    {
        $hours = [];
        $span = self::END - self::START;
        for ($hour = self::START; $hour <= self::END; $hour++) {
            $hours[] = [
                'label' => sprintf('%02dh', $hour),
                'top' => (($hour - self::START) / $span) * 100,
            ];
        }

        return $hours;
    }

    public static function monday(?string $value = null): Carbon
    {
        $date = $value ? Carbon::createFromFormat('Y-m-d', $value) : Carbon::now();

        return $date->copy()->locale('fr')->startOfWeek(Carbon::MONDAY);
    }

    public static function minutes(string $time): int
    {
        $parts = explode(':', substr($time, 0, 5));

        return ((int) ($parts[0] ?? 0)) * 60 + (int) ($parts[1] ?? 0);
    }

    public static function label(string $time): string
    {
        $parts = explode(':', substr($time, 0, 5));
        $hour = str_pad((string) ((int) ($parts[0] ?? 0)), 2, '0', STR_PAD_LEFT);
        $minute = str_pad((string) ((int) ($parts[1] ?? 0)), 2, '0', STR_PAD_LEFT);

        return $minute === '00' ? $hour.'h' : $hour.'h'.$minute;
    }

    public static function nowTop(Carbon $monday): ?float
    {
        $today = Carbon::now();
        $currentMonday = $today->copy()->locale('fr')->startOfWeek(Carbon::MONDAY);
        if (! $currentMonday->isSameDay($monday) || $today->isoWeekday() > 5) {
            return null;
        }

        $minutes = ($today->hour * 60) + $today->minute;
        $start = self::START * 60;
        $end = self::END * 60;
        if ($minutes < $start || $minutes > $end) {
            return null;
        }

        return (($minutes - $start) / ($end - $start)) * 100;
    }

    /**
     * @param  Collection<int, EcoleSeance>  $seances
     * @return list<array{label: string, number: string, isToday: bool, events: list<array{matiere: string, professeur: ?string, salle: ?string, horaire: string, top: float, height: float, left: float, width: float}>}>
     */
    public static function columns(Carbon $monday, Collection $seances): array
    {
        $byDay = $seances->groupBy(function (EcoleSeance $seance) {
            if ($seance->date) {
                return Carbon::parse($seance->date)->isoWeekday();
            }

            return (int) $seance->jour;
        });
        $columns = [];

        foreach (self::days() as $jour => $label) {
            $date = $monday->copy()->addDays($jour - 1);
            $columns[] = [
                'label' => $label,
                'number' => $date->format('d'),
                'isToday' => $date->isSameDay(Carbon::now()),
                'events' => self::layout($byDay->get($jour, collect())),
            ];
        }

        return $columns;
    }

    /**
     * @param  Collection<int, EcoleSeance>  $seances
     * @return list<array{matiere: string, professeur: ?string, salle: ?string, horaire: string, top: float, height: float, left: float, width: float}>
     */
    public static function layout(Collection $seances): array
    {
        $span = (self::END - self::START) * 60;
        $items = $seances
            ->map(function (EcoleSeance $seance) {
                $start = max(self::START * 60, self::minutes($seance->heure('heure_debut')));
                $end = min(self::END * 60, self::minutes($seance->heure('heure_fin')));

                return [
                    'start' => $start,
                    'end' => max($start + 15, $end),
                    'matiere' => $seance->matiere,
                    'professeur' => $seance->professeur,
                    'salle' => $seance->salle,
                    'horaire' => self::label($seance->heure('heure_debut')).' – '.self::label($seance->heure('heure_fin')),
                ];
            })
            ->sortBy('start')
            ->values();

        $columnEnds = [];
        $placed = [];
        foreach ($items as $item) {
            $column = null;
            foreach ($columnEnds as $index => $end) {
                if ($item['start'] >= $end) {
                    $column = $index;
                    $columnEnds[$index] = $item['end'];
                    break;
                }
            }
            if ($column === null) {
                $column = count($columnEnds);
                $columnEnds[] = $item['end'];
            }
            $item['column'] = $column;
            $placed[] = $item;
        }

        $count = max(1, count($columnEnds));

        return array_map(function (array $item) use ($count, $span) {
            return [
                'matiere' => $item['matiere'],
                'professeur' => $item['professeur'],
                'salle' => $item['salle'],
                'horaire' => $item['horaire'],
                'top' => (($item['start'] - (self::START * 60)) / $span) * 100,
                'height' => (($item['end'] - $item['start']) / $span) * 100,
                'left' => ($item['column'] / $count) * 100,
                'width' => (1 / $count) * 100,
            ];
        }, $placed);
    }

    /**
     * Week grid for the timetable form: the chosen group, teacher and room,
     * plus a preview of the slot being created. Overlaps sit side by side.
     *
     * @param  Collection<int, EcoleSeance>  $seances
     * @param  array{date?: string, debut?: string, fin?: string}|null  $apercu
     * @return array{columns: list<array<string, mixed>>, conflit: bool}
     */
    public static function disponibilites(
        Carbon $monday,
        Collection $seances,
        ?int $groupeId,
        ?int $enseignantId,
        ?int $salleId,
        ?array $apercu = null
    ): array {
        $items = [];
        foreach ($seances as $seance) {
            $date = $seance->date?->toDateString();
            if (! $date) {
                continue;
            }
            $groupe = $groupeId && (int) $seance->ecole_groupe_id === $groupeId;
            $prof = $enseignantId && (int) $seance->ecole_enseignant_id === $enseignantId;
            $salle = $salleId && (int) $seance->ecole_salle_id === $salleId;
            if (! $groupe && ! $prof && ! $salle) {
                continue;
            }

            $kind = $groupe ? 'groupe' : ($prof ? 'enseignant' : 'salle');
            $tags = array_values(array_filter([
                $groupe ? 'Groupe' : null,
                $prof ? 'Prof' : null,
                $salle ? 'Salle' : null,
            ]));
            $start = self::minutes($seance->heure('heure_debut'));
            $end = self::minutes($seance->heure('heure_fin'));
            $items[] = [
                'id' => $seance->id,
                'date' => $date,
                'start' => $start,
                'end' => $end,
                'kind' => $kind,
                'titre' => $seance->matiere,
                'ligne' => $groupe ? (string) ($seance->professeur ?: '') : (string) ($seance->groupe?->nom ?: ''),
                'meta' => trim(implode(' · ', array_filter([
                    $seance->salle,
                    self::label($seance->heure('heure_debut')).' – '.self::label($seance->heure('heure_fin')),
                ]))),
                'tags' => implode(' · ', $tags),
                'supprimable' => $groupe,
            ];
        }

        $apercuItem = self::apercu($monday, $apercu);
        if ($apercuItem) {
            $items[] = $apercuItem;
        }

        $conflit = false;
        if ($apercuItem) {
            foreach ($items as $item) {
                if (($item['kind'] ?? '') === 'apercu' || $item['date'] !== $apercuItem['date']) {
                    continue;
                }
                if ($item['start'] < $apercuItem['end'] && $apercuItem['start'] < $item['end']) {
                    $conflit = true;
                    break;
                }
            }
        }

        $byDay = collect($items)->groupBy(fn (array $item) => Carbon::parse($item['date'])->isoWeekday());
        $focus = $apercu['date'] ?? null;
        $columns = [];
        foreach (self::days() as $jour => $label) {
            $date = $monday->copy()->addDays($jour - 1);
            $columns[] = [
                'label' => $label,
                'number' => $date->format('j'),
                'date' => $date->toDateString(),
                'isToday' => $date->isSameDay(Carbon::now()),
                'isSelected' => $focus && $date->toDateString() === $focus,
                'events' => self::placer($byDay->get($jour, collect())->all()),
            ];
        }

        return ['columns' => $columns, 'conflit' => $conflit];
    }

    /**
     * @param  array{date?: string, debut?: string, fin?: string}|null  $apercu
     * @return array<string, mixed>|null
     */
    private static function apercu(Carbon $monday, ?array $apercu): ?array
    {
        $date = $apercu['date'] ?? null;
        $debut = $apercu['debut'] ?? null;
        $fin = $apercu['fin'] ?? null;
        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! is_string($debut) || ! is_string($fin)) {
            return null;
        }

        try {
            $jour = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        $limite = $monday->copy()->addDays(4)->endOfDay();
        if ($jour->lt($monday->copy()->startOfDay()) || $jour->gt($limite) || $jour->isoWeekday() > 5) {
            return null;
        }

        $start = self::minutes(substr($debut, 0, 5));
        $end = self::minutes(substr($fin, 0, 5));
        if ($end <= $start) {
            return null;
        }

        return [
            'id' => null,
            'date' => $jour->toDateString(),
            'start' => $start,
            'end' => $end,
            'kind' => 'apercu',
            'titre' => 'Nouveau créneau',
            'ligne' => '',
            'meta' => self::label(substr($debut, 0, 5)).' – '.self::label(substr($fin, 0, 5)),
            'tags' => 'Aperçu',
            'supprimable' => false,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private static function placer(array $items): array
    {
        $span = (self::END - self::START) * 60;
        $normalized = collect($items)
            ->map(function (array $item) {
                $start = max(self::START * 60, (int) $item['start']);
                $end = min(self::END * 60, (int) $item['end']);
                $item['start'] = $start;
                $item['end'] = max($start + 15, $end);

                return $item;
            })
            ->sortBy('start')
            ->values();

        $columnEnds = [];
        $placed = [];
        foreach ($normalized as $item) {
            $column = null;
            foreach ($columnEnds as $index => $end) {
                if ($item['start'] >= $end) {
                    $column = $index;
                    $columnEnds[$index] = $item['end'];
                    break;
                }
            }
            if ($column === null) {
                $column = count($columnEnds);
                $columnEnds[] = $item['end'];
            }
            $item['column'] = $column;
            $placed[] = $item;
        }

        $count = max(1, count($columnEnds));

        return array_map(function (array $item) use ($count, $span) {
            $item['top'] = (($item['start'] - (self::START * 60)) / $span) * 100;
            $item['height'] = (($item['end'] - $item['start']) / $span) * 100;
            $item['left'] = ($item['column'] / $count) * 100;
            $item['width'] = (1 / $count) * 100;

            return $item;
        }, $placed);
    }
}
