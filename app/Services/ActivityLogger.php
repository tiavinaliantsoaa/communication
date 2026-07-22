<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public function log(
        string $module,
        string $description,
        ?User $user = null,
        string $action = 'update',
        ?string $titre = null,
        ?string $url = null,
        ?Model $subject = null
    ): ActivityLog {
        $user = $user ?? auth()->user();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'module' => $module,
            'action' => $action,
            'titre' => $titre ?? (ActivityLog::MODULES[$module] ?? 'Activité'),
            'description' => $description,
            'url' => $url,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
        ]);
    }
}
