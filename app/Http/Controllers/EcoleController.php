<?php

namespace App\Http\Controllers;

class EcoleController extends Controller
{
    public function dashboard()
    {
        return $this->page('Dashboard', 'Les indicateurs de la gestion école apparaîtront ici.');
    }

    public function etudiants()
    {
        return $this->page('Gestion Etudiants', 'Aucun étudiant pour le moment.');
    }

    public function classes()
    {
        return $this->page('Gestion Classe', 'Aucune classe pour le moment.');
    }

    public function groupes()
    {
        return $this->page('Gestion Groupe', 'Aucun groupe pour le moment.');
    }

    public function emplois()
    {
        return $this->page('Emplois du temps', 'Aucun emploi du temps pour le moment.');
    }

    public function absences()
    {
        return $this->page('Retard et Absence', 'Aucun retard ni absence pour le moment.');
    }

    public function bulletins()
    {
        return $this->page('Bulletin de note', 'Aucun bulletin pour le moment.');
    }

    private function page(string $title, string $empty)
    {
        return view('ecole.index', [
            'title' => $title,
            'subtitle' => 'Gestion École',
            'empty' => $empty,
        ]);
    }
}
