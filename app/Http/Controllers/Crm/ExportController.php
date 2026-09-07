<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmCandidate;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Crm\CandidateExcelExporter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExportController extends Controller
{
    public function index(Request $request, CandidateExcelExporter $exporter)
    {
        $filters = $exporter->filtersFromRequest($request);
        $count = $exporter->query($filters)->count();

        return view('crm.export', [
            'filters' => $filters,
            'count' => $count,
            'columns' => CandidateExcelExporter::COLUMNS,
            'columnGroups' => CandidateExcelExporter::COLUMN_GROUPS,
            'defaultColumns' => CandidateExcelExporter::DEFAULT_COLUMNS,
            'populations' => CandidateExcelExporter::POPULATIONS,
            'programmes' => $exporter->programmeOptions(),
            'intakes' => $exporter->intakeOptions(),
            'statuts' => CrmCandidate::STATUTS,
            'advisors' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function download(Request $request, CandidateExcelExporter $exporter)
    {
        $request->validate([
            'population' => ['required', Rule::in(array_keys(CandidateExcelExporter::POPULATIONS))],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['string', Rule::in(array_keys(CandidateExcelExporter::COLUMNS))],
            'programme' => ['nullable', 'array'],
            'programme.*' => ['string', 'max:255'],
            'intake' => ['nullable', 'array'],
            'intake.*' => ['string', 'max:50'],
            'statut' => ['nullable', 'string', Rule::in(array_keys(CrmCandidate::STATUTS))],
            'advisor_id' => ['nullable', 'exists:users,id'],
        ], [
            'columns.required' => 'Choisissez au moins une colonne à exporter.',
            'columns.min' => 'Choisissez au moins une colonne à exporter.',
        ]);

        $filters = $exporter->filtersFromRequest($request);
        $candidates = $exporter->query($filters)->get();

        if ($candidates->isEmpty()) {
            return redirect()->route('crm.export')
                ->withInput()
                ->with('error', 'Aucun candidat ne correspond aux filtres choisis.');
        }

        $binary = $exporter->xlsx($candidates, $filters);
        $filename = 'candidats-'.now()->format('Y-m-d').'.xlsx';

        app(ActivityLogger::class)->log(
            'crm',
            auth()->user()->name.' a exporté '.$candidates->count().' candidat(s) CRM',
            auth()->user(),
            'export',
            'CRM',
            route('crm.export')
        );

        return response()->streamDownload(function () use ($binary) {
            echo $binary;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
