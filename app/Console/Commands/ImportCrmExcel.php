<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Crm\CrmExcelImporter;
use Illuminate\Console\Command;

class ImportCrmExcel extends Command
{
    protected $signature = 'crm:import-excel
                            {--candidates= : Classeur des candidats. Défaut : storage/app/imports/crm/crm_candidates.xlsx}
                            {--documents= : Classeur des liens de documents. Défaut : storage/app/imports/crm/crm_candidates_documents.xlsx}
                            {--user= : E-mail du compte auteur de l’import}
                            {--update : Mettre à jour les candidats déjà importés}
                            {--dry-run : Simuler sans écrire en base ni télécharger}
                            {--skip-downloads : Enregistrer les liens sans télécharger les fichiers}
                            {--with-profile-files : Télécharger aussi photo, CV et lettre de motivation présents dans le classeur candidats}
                            {--skip-candidates : Ne pas relire le classeur candidats, seulement les documents}
                            {--limit= : Nombre maximum de fichiers à télécharger}';

    protected $description = 'Importer les candidats CRM depuis Excel et télécharger leurs documents';

    public function handle(CrmExcelImporter $importer): int
    {
        $candidatesPath = $this->option('skip-candidates')
            ? null
            : $this->resolvePath($this->option('candidates'), 'crm_candidates.xlsx');
        $documentsPath = $this->resolvePath($this->option('documents'), 'crm_candidates_documents.xlsx');

        if ($candidatesPath !== null && ! is_file($candidatesPath)) {
            $this->error('Fichier candidats introuvable : '.$candidatesPath);

            return self::FAILURE;
        }
        if (! is_file($documentsPath)) {
            if ($candidatesPath === null) {
                $this->error('Fichier documents introuvable : '.$documentsPath);

                return self::FAILURE;
            }
            $this->warn('Fichier documents introuvable, import des candidats uniquement : '.$documentsPath);
            $documentsPath = null;
        }

        $user = $this->resolveUser();
        if (! $user) {
            return self::FAILURE;
        }

        $limit = $this->option('limit');
        $this->line('Candidats : '.($candidatesPath ?? 'déjà en base'));
        $this->line('Documents : '.($documentsPath ?? 'aucun'));
        $this->line('Auteur : '.$user->email.($this->option('dry-run') ? ' — simulation' : ''));

        $bars = [];
        $progress = function (string $phase, int $current, int $total) use (&$bars): void {
            if ($total < 1) {
                return;
            }
            if (! isset($bars[$phase])) {
                $this->newLine();
                $this->line($phase === 'candidates' ? 'Candidats' : 'Documents');
                $bars[$phase] = $this->output->createProgressBar($total);
                $bars[$phase]->start();
            }
            $bars[$phase]->setProgress(min($current, $total));
        };

        try {
            $report = $importer->import($candidatesPath, $documentsPath, [
                'dry_run' => (bool) $this->option('dry-run'),
                'update_existing' => (bool) $this->option('update'),
                'download' => ! $this->option('skip-downloads'),
                'download_limit' => $limit === null || $limit === '' ? null : (int) $limit,
                'profile_files' => (bool) $this->option('with-profile-files'),
                'create_lookups' => true,
                'progress' => $progress,
            ], $user);
        } catch (\Throwable $exception) {
            $this->newLine();
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($bars as $bar) {
            $bar->finish();
        }
        $this->newLine(2);

        $dryRun = (bool) ($report['dry_run'] ?? false);
        $this->table(['Indicateur', 'Nombre'], [
            ['Candidats créés', $report['candidates_created']],
            ['Candidats mis à jour', $report['candidates_updated']],
            ['Candidats déjà présents', $report['candidates_skipped_existing']],
            ['Candidats ignorés', $report['candidates_skipped_invalid']],
            ['Documents créés', $report['documents_created']],
            ['Documents mis à jour', $report['documents_updated']],
            ['Documents déjà téléchargés', $report['documents_skipped_existing']],
            ['Documents sans candidat', $report['documents_skipped_unmatched']],
            ['Documents ignorés', $report['documents_skipped_invalid']],
            [$dryRun ? 'Fichiers qui seraient téléchargés' : 'Fichiers téléchargés', $report['documents_downloaded']],
            ['Téléchargements en échec', $report['documents_download_failed']],
            ['Fichiers reportés (limite)', $report['documents_deferred']],
            ['Programmes ajoutés', $report['programmes_created']],
            ['Années académiques ajoutées', $report['intakes_created']],
            ['Types de document ajoutés', $report['document_types_created']],
        ]);

        foreach ($report['errors'] as $error) {
            $this->warn($error);
        }

        $reportPath = $this->writeReport($report);
        $this->info($dryRun ? 'Simulation terminée. Aucune donnée enregistrée.' : 'Import terminé.');
        if ($reportPath !== null) {
            $this->line('Rapport : '.$reportPath);
        }

        if (! $dryRun && ($report['candidates_created'] + $report['candidates_updated'] + $report['documents_created'] + $report['documents_downloaded']) > 0) {
            app(ActivityLogger::class)->log(
                'crm',
                'Import Excel : '.$report['candidates_created'].' candidat(s) créé(s), '.$report['documents_downloaded'].' fichier(s) téléchargé(s).',
                $user,
                'create',
                'CRM'
            );
        }

        return $report['errors'] !== [] && $report['candidates_created'] === 0 && $report['documents_created'] === 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function resolveUser(): ?User
    {
        $email = trim((string) $this->option('user'));
        if ($email !== '') {
            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                $this->error('Aucun utilisateur avec l’e-mail '.$email.'.');
            }

            return $user;
        }

        $user = User::query()->where('role', 'super_admin')->orderBy('id')->first()
            ?? User::query()->where('role', 'administrateur')->orderBy('id')->first();

        if (! $user) {
            $this->error('Aucun administrateur en base. Relancez avec --user=email@exemple.com.');
        }

        return $user;
    }

    private function resolvePath(?string $option, string $defaultName): string
    {
        $path = trim((string) $option);
        if ($path === '') {
            return storage_path('app/imports/crm/'.$defaultName);
        }

        if (preg_match('/^(?:[A-Za-z]:[\\\\\\/]|\/)/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function writeReport(array $report): ?string
    {
        if (app()->runningUnitTests()) {
            return null;
        }
        $directory = storage_path('app/imports/crm/reports');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory.'/import-'.now()->format('Ymd-His').'.txt';
        $lines = [];
        foreach ($report as $key => $value) {
            if ($key === 'errors') {
                continue;
            }
            $lines[] = $key.' : '.(is_bool($value) ? ($value ? 'oui' : 'non') : $value);
        }
        foreach ($report['errors'] as $error) {
            $lines[] = 'erreur : '.$error;
        }
        file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);

        return $path;
    }
}
