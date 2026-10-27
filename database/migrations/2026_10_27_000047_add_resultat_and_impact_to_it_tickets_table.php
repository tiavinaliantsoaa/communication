<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('it_tickets')) {
            return;
        }

        $hasResultat = Schema::hasColumn('it_tickets', 'resultat_attendu');
        $hasImpact = Schema::hasColumn('it_tickets', 'impact');

        if ($hasResultat && $hasImpact) {
            return;
        }

        Schema::table('it_tickets', function (Blueprint $table) use ($hasResultat, $hasImpact) {
            if (! $hasResultat) {
                $table->text('resultat_attendu')->nullable()->after('description');
            }
            if (! $hasImpact) {
                $table->string('impact', 32)->nullable()->after($hasResultat ? 'resultat_attendu' : 'description');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('it_tickets')) {
            return;
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('it_tickets', 'resultat_attendu') ? 'resultat_attendu' : null,
            Schema::hasColumn('it_tickets', 'impact') ? 'impact' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table('it_tickets', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};
