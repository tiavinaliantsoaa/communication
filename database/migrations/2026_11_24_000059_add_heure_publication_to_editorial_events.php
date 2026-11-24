<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editorial_events', function (Blueprint $table) {
            $table->time('heure_publication')->nullable()->after('date_fin');
        });
    }

    public function down(): void
    {
        Schema::table('editorial_events', function (Blueprint $table) {
            $table->dropColumn('heure_publication');
        });
    }
};
