<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editorial_events', function (Blueprint $table) {
            if (! Schema::hasColumn('editorial_events', 'type_contenu')) {
                $table->string('type_contenu')->nullable()->after('categorie');
            }
            if (! Schema::hasColumn('editorial_events', 'booster')) {
                $table->boolean('booster')->default(false)->after('type_contenu');
            }
            if (! Schema::hasColumn('editorial_events', 'valide')) {
                $table->boolean('valide')->default(false)->after('statut');
            }
            if (! Schema::hasColumn('editorial_events', 'texte_publication')) {
                $table->text('texte_publication')->nullable()->after('notes');
            }
        });

        if (Schema::hasColumn('editorial_events', 'notes')) {
            foreach (DB::table('editorial_events')->orderBy('id')->get() as $row) {
                DB::table('editorial_events')
                    ->where('id', $row->id)
                    ->update(['texte_publication' => $row->notes]);
            }
        }

        Schema::table('editorial_events', function (Blueprint $table) {
            if (Schema::hasColumn('editorial_events', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('editorial_events', 'canal')) {
                $table->dropColumn('canal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('editorial_events', function (Blueprint $table) {
            $table->text('notes')->nullable();
            $table->string('canal')->nullable();
        });

        if (Schema::hasColumn('editorial_events', 'texte_publication')) {
            foreach (DB::table('editorial_events')->orderBy('id')->get() as $row) {
                DB::table('editorial_events')
                    ->where('id', $row->id)
                    ->update(['notes' => $row->texte_publication]);
            }
        }

        Schema::table('editorial_events', function (Blueprint $table) {
            $table->dropColumn(['type_contenu', 'booster', 'valide', 'texte_publication']);
        });
    }
};
