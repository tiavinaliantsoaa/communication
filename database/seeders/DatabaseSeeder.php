<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\BudgetAnnuel;
use App\Models\Campagne;
use App\Models\Departement;
use App\Models\Depense;
use App\Models\Fournisseur;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Seeding is disabled in production. Use: php artisan escm:create-admin');

            return;
        }

        User::create([
            'name' => 'Matthieu R.',
            'email' => 'matthieu@escm.mg',
            'password' => 'password',
            'role' => 'responsable_communication',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Admin ESCM',
            'email' => 'admin@escm.mg',
            'password' => 'password',
            'role' => 'administrateur',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@escm.mg',
            'password' => 'password',
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        BudgetAnnuel::create([
            'annee' => 2026,
            'montant' => 240000000,
        ]);

        for ($m = 1; $m <= 12; $m++) {
            Budget::create([
                'montant' => 20000000,
                'annee' => 2026,
                'mois' => $m,
            ]);
        }

        $fournisseurs = [
            ['nom' => 'Facebook Ads', 'telephone' => '034 00 000 01', 'email' => 'contact@facebook.com'],
            ['nom' => 'Imprimerie Rapid', 'telephone' => '034 00 000 02', 'email' => 'info@rapid.mg'],
            ['nom' => 'Studio Vision', 'telephone' => '034 00 000 03', 'email' => 'studio@vision.mg'],
            ['nom' => 'Event Pro MG', 'telephone' => '034 00 000 04', 'email' => 'event@pro.mg'],
            ['nom' => 'Goodies Express', 'telephone' => '034 00 000 05', 'email' => 'goodies@express.mg'],
        ];

        foreach ($fournisseurs as $f) {
            Fournisseur::create($f);
        }

        Campagne::create(['nom' => 'MBA 2026', 'budget' => 5000000, 'date_debut' => '2026-01-01', 'date_fin' => '2026-12-31', 'statut' => 'active']);
        Campagne::create(['nom' => 'Bachelor Digital', 'budget' => 3000000, 'date_debut' => '2026-03-01', 'date_fin' => '2026-09-30', 'statut' => 'active']);
        Campagne::create(['nom' => 'Portes Ouvertes', 'budget' => 2000000, 'date_debut' => '2026-05-01', 'date_fin' => '2026-06-30', 'statut' => 'active']);
        Campagne::create(['nom' => 'Master Finance', 'budget' => 1500000, 'date_debut' => '2026-02-01', 'date_fin' => '2026-08-31', 'statut' => 'active']);
        Campagne::create(['nom' => 'LICENCE Marketing', 'budget' => 1000000, 'date_debut' => '2026-04-01', 'date_fin' => '2026-10-31', 'statut' => 'active']);

        $depensesJuin = [
            ['fournisseur' => 'Facebook Ads', 'objet' => 'Campagne MBA Facebook', 'campagne' => 'MBA 2026', 'montant' => 3500000, 'statut' => 'paye', 'categorie' => 'sponsoring_reseaux', 'date_depense' => '2026-06-05'],
            ['fournisseur' => 'Facebook Ads', 'objet' => 'Campagne Bachelor Instagram', 'campagne' => 'Bachelor Digital', 'montant' => 2500000, 'statut' => 'paye', 'categorie' => 'sponsoring_reseaux', 'date_depense' => '2026-06-08'],
            ['fournisseur' => 'Facebook Ads', 'objet' => 'LinkedIn Ads Master', 'campagne' => 'Master Finance', 'montant' => 2500000, 'statut' => 'en_attente', 'categorie' => 'sponsoring_reseaux', 'date_depense' => '2026-06-10'],
            ['fournisseur' => 'Studio Vision', 'objet' => 'Vidéo promotionnelle', 'campagne' => 'MBA 2026', 'montant' => 1800000, 'statut' => 'valide', 'categorie' => 'production_contenu', 'date_depense' => '2026-06-03'],
            ['fournisseur' => 'Studio Vision', 'objet' => 'Photos campus', 'campagne' => 'Portes Ouvertes', 'montant' => 1200000, 'statut' => 'paye', 'categorie' => 'production_contenu', 'date_depense' => '2026-06-07'],
            ['fournisseur' => 'Imprimerie Rapid', 'objet' => 'Flyers Bachelor', 'campagne' => 'Bachelor Digital', 'montant' => 1200000, 'statut' => 'paye', 'categorie' => 'impression', 'date_depense' => '2026-06-02'],
            ['fournisseur' => 'Imprimerie Rapid', 'objet' => 'Brochures MBA', 'campagne' => 'MBA 2026', 'montant' => 800000, 'statut' => 'paye', 'categorie' => 'impression', 'date_depense' => '2026-06-04'],
            ['fournisseur' => 'Event Pro MG', 'objet' => 'Stand Portes Ouvertes', 'campagne' => 'Portes Ouvertes', 'montant' => 1000000, 'statut' => 'valide', 'categorie' => 'goodies_evenements', 'date_depense' => '2026-06-06'],
            ['fournisseur' => 'Goodies Express', 'objet' => 'Goodies étudiants', 'campagne' => 'LICENCE Marketing', 'montant' => 750000, 'statut' => 'en_attente', 'categorie' => 'goodies_evenements', 'date_depense' => '2026-06-09'],
        ];

        foreach ($depensesJuin as $d) {
            Depense::create($d);
        }

        // Dépenses autres mois pour le graphique d'évolution
        $autresMois = [
            ['m' => 1, 'montant' => 12000000], ['m' => 2, 'montant' => 13500000],
            ['m' => 3, 'montant' => 14800000], ['m' => 4, 'montant' => 14200000],
            ['m' => 5, 'montant' => 15500000],
        ];

        foreach ($autresMois as $item) {
            Depense::create([
                'fournisseur' => 'Facebook Ads',
                'objet' => 'Campagne mensuelle',
                'campagne' => 'MBA 2026',
                'montant' => $item['montant'],
                'statut' => 'paye',
                'categorie' => 'sponsoring_reseaux',
                'date_depense' => "2026-{$item['m']}-15",
            ]);
        }

        Stock::create(['article' => 'Flyers Bachelor', 'quantite' => 45, 'seuil_alerte' => 100]);
        Stock::create(['article' => 'Brochures MBA', 'quantite' => 320, 'seuil_alerte' => 150]);
        Stock::create(['article' => 'Roll-up Portes Ouvertes', 'quantite' => 8, 'seuil_alerte' => 5]);
        Stock::create(['article' => 'Goodies stylos', 'quantite' => 150, 'seuil_alerte' => 100]);
        Stock::create(['article' => 'Kakemonos', 'quantite' => 12, 'seuil_alerte' => 10]);

        $this->call(EditorialEventSeeder::class);
        $this->call(ProjetSeeder::class);

        Departement::backfillUnassigned();
    }
}
