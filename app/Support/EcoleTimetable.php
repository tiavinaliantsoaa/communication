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
     * @return list<array{label: string, number: string, isToday: bool, events: list<array{matiere: string, salle: ?string, horaire: string, top: float, height: float, left: float, width: float}>}>
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
     * @return list<array{matiere: string, salle: ?string, horaire: string, top: float, height: float, left: float, width: float}>
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
                'salle' => $item['salle'],
                'horaire' => $item['horaire'],
                'top' => (($item['start'] - (self::START * 60)) / $span) * 100,
                'height' => (($item['end'] - $item['start']) / $span) * 100,
                'left' => ($item['column'] / $count) * 100,
                'width' => (1 / $count) * 100,
            ];
        }, $placed);
    }
}
