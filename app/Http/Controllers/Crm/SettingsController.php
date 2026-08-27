<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmCandidate;
use App\Models\CrmDocumentType;
use App\Models\CrmIntake;
use App\Models\CrmProgramme;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        $intakes = CrmIntake::ordered()->get();
        $programmes = CrmProgramme::ordered()->get();
        $documentTypes = CrmDocumentType::ordered()->get();
        $candidatesCount = CrmCandidate::query()->count();

        return view('crm.settings', compact('intakes', 'programmes', 'documentTypes', 'candidatesCount'));
    }

    public function storeIntake(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:100', 'unique:crm_intakes,label'],
        ], [
            'label.required' => 'Le libellé de la rentrée est obligatoire.',
            'label.unique' => 'Cette rentrée existe déjà.',
        ]);

        $max = (int) CrmIntake::max('position');

        CrmIntake::create([
            'label' => trim($data['label']),
            'actif' => true,
            'position' => $max + 1,
        ]);

        return back()->with('success', 'Rentrée ajoutée.');
    }

    public function updateIntake(Request $request, CrmIntake $intake)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:100', Rule::unique('crm_intakes', 'label')->ignore($intake->id)],
            'actif' => ['sometimes', 'boolean'],
        ]);

        $intake->update([
            'label' => trim($data['label']),
            'actif' => $request->boolean('actif', $intake->actif),
        ]);

        return back()->with('success', 'Rentrée mise à jour.');
    }

    public function destroyIntake(CrmIntake $intake)
    {
        $intake->delete();

        return back()->with('success', 'Rentrée supprimée.');
    }

    public function toggleIntake(CrmIntake $intake)
    {
        $intake->update(['actif' => ! $intake->actif]);

        return back()->with('success', $intake->actif ? 'Rentrée activée.' : 'Rentrée désactivée.');
    }

    public function storeProgramme(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255', 'unique:crm_programmes,label'],
        ], [
            'label.required' => 'Le libellé du programme est obligatoire.',
            'label.unique' => 'Ce programme existe déjà.',
        ]);

        $max = (int) CrmProgramme::max('position');

        CrmProgramme::create([
            'label' => trim($data['label']),
            'actif' => true,
            'position' => $max + 1,
        ]);

        return back()->with('success', 'Programme ajouté.');
    }

    public function destroyProgramme(CrmProgramme $programme)
    {
        $programme->delete();

        return back()->with('success', 'Programme supprimé.');
    }

    public function toggleProgramme(CrmProgramme $programme)
    {
        $programme->update(['actif' => ! $programme->actif]);

        return back()->with('success', $programme->actif ? 'Programme activé.' : 'Programme désactivé.');
    }

    public function storeDocumentType(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255', 'unique:crm_document_types,label'],
            'is_required' => ['sometimes', 'boolean'],
        ], [
            'label.required' => 'Le nom du document est obligatoire.',
            'label.unique' => 'Ce type de document existe déjà.',
        ]);

        $max = (int) CrmDocumentType::max('position');

        CrmDocumentType::create([
            'label' => trim($data['label']),
            'is_required' => $request->boolean('is_required'),
            'actif' => true,
            'position' => $max + 1,
        ]);

        return back()->with('success', 'Type de document ajouté.');
    }

    public function toggleDocumentTypeRequired(CrmDocumentType $documentType)
    {
        $documentType->update(['is_required' => ! $documentType->is_required]);

        return back()->with('success', $documentType->is_required ? 'Document marqué comme requis.' : 'Document marqué comme optionnel.');
    }

    public function toggleDocumentType(CrmDocumentType $documentType)
    {
        $documentType->update(['actif' => ! $documentType->actif]);

        return back()->with('success', $documentType->actif ? 'Type de document activé.' : 'Type de document désactivé.');
    }

    public function destroyDocumentType(CrmDocumentType $documentType)
    {
        $documentType->delete();

        return back()->with('success', 'Type de document supprimé.');
    }
}
