<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
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

        return view('crm.settings', compact('intakes', 'programmes'));
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
}
