<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmIntake;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        $intakes = CrmIntake::ordered()->get();

        return view('crm.settings', compact('intakes'));
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
}
