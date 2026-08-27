<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\Crm\GlideImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    public function store(Request $request, GlideImporter $importer)
    {
        $data = $request->validate([
            'candidates_csv' => ['nullable', 'file', 'max:20480'],
            'documents_csv' => ['nullable', 'file', 'max:20480'],
            'mode' => ['nullable', 'in:import,dry_run'],
            'skip_abandoned' => ['sometimes', 'boolean'],
            'update_existing' => ['sometimes', 'boolean'],
            'create_lookups' => ['sometimes', 'boolean'],
            'filter_2025_2026' => ['sometimes', 'boolean'],
        ], [
            'candidates_csv.max' => 'Le fichier candidats ne doit pas dépasser 20 Mo.',
            'documents_csv.max' => 'Le fichier documents ne doit pas dépasser 20 Mo.',
        ]);

        $candidates = $request->file('candidates_csv');
        $documents = $request->file('documents_csv');

        if (! $candidates && ! $documents) {
            return back()->withErrors([
                'candidates_csv' => 'Ajoutez au moins un fichier CSV (candidats et/ou documents).',
            ]);
        }

        foreach (['candidates_csv' => $candidates, 'documents_csv' => $documents] as $field => $file) {
            if ($file && ! in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'], true)) {
                return back()->withErrors([
                    $field => 'Le fichier doit être un CSV.',
                ]);
            }
        }

        $stored = [];
        try {
            $candidatesPath = null;
            $documentsPath = null;

            if ($candidates) {
                $stored[] = $candidates->store('crm-imports');
                $candidatesPath = Storage::path(end($stored));
            }
            if ($documents) {
                $stored[] = $documents->store('crm-imports');
                $documentsPath = Storage::path(end($stored));
            }

            $dryRun = ($data['mode'] ?? 'import') === 'dry_run';

            $report = $importer->import($candidatesPath, $documentsPath, [
                'dry_run' => $dryRun,
                'skip_abandoned' => $request->boolean('skip_abandoned'),
                'update_existing' => $request->boolean('update_existing'),
                'create_lookups' => $request->boolean('create_lookups', true),
                'academic_year' => $request->boolean('filter_2025_2026', true) ? '2025-2026' : 'all',
            ], $request->user());
        } finally {
            foreach ($stored as $path) {
                Storage::delete($path);
            }
        }

        if (! ($report['dry_run'] ?? false) && ($report['candidates_created'] + $report['candidates_updated'] + $report['documents_created'] + $report['documents_updated']) > 0) {
            app(ActivityLogger::class)->log(
                'crm',
                'Import Glide : '.$report['candidates_created'].' candidat(s) créé(s), '.$report['documents_created'].' document(s) lié(s).',
                $request->user(),
                'create',
                'CRM',
                route('crm.settings')
            );
        }

        $message = ($report['dry_run'] ?? false)
            ? 'Simulation terminée — aucune donnée n’a été enregistrée.'
            : 'Import Glide terminé.';

        return back()->with('success', $message)->with('import_report', $report);
    }

    public function destroyAll(Request $request, GlideImporter $importer)
    {
        $counts = $importer->purgeAll();

        app(ActivityLogger::class)->log(
            'crm',
            'Suppression de toutes les données CRM : '.$counts['candidates'].' candidat(s), '.$counts['documents'].' document(s).',
            $request->user(),
            'delete',
            'CRM',
            route('crm.settings')
        );

        return back()->with(
            'success',
            $counts['candidates'] === 0
                ? 'Aucune donnée CRM à supprimer.'
                : $counts['candidates'].' candidat(s) et '.$counts['documents'].' document(s) ont été supprimés. Vous pouvez relancer un import.'
        );
    }
}
