<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_candidates', function (Blueprint $table) {
            $table->string('escm_tour_ville', 120)->nullable()->after('facebook_profil_url');
        });
    }

    public function down(): void
    {
        Schema::table('crm_candidates', function (Blueprint $table) {
            $table->dropColumn('escm_tour_ville');
        });
    }
};
