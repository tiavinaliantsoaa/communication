<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActiviteController extends Controller
{
    public function index(Request $request)
    {
        $module = $request->string('module')->toString();

        $activites = ActivityLog::with('user')
            ->when($module !== '' && isset(ActivityLog::MODULES[$module]), fn ($q) => $q->where('module', $module))
            ->orderByDesc('created_at')
            ->paginate(40)
            ->withQueryString();

        return view('activites.index', [
            'title' => 'Activité',
            'subtitle' => 'Historique des actions dans tout l\'outil',
            'activites' => $activites,
            'modules' => ActivityLog::MODULES,
            'moduleActif' => $module,
        ]);
    }
}
