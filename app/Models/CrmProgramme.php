<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmProgramme extends Model
{
    protected $fillable = [
        'label',
        'actif',
        'position',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'position' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('actif', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('label');
    }

    /**
     * @return array<int, string>
     */
    public static function optionsForSelect(?string $include = null): array
    {
        $labels = static::active()->ordered()->pluck('label')->all();

        if ($include && ! in_array($include, $labels, true)) {
            array_unshift($labels, $include);
        }

        return $labels;
    }
}
