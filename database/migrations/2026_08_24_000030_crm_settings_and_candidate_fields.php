<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_intakes', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100);
            $table->boolean('actif')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::table('crm_candidates', function (Blueprint $table) {
            $table->string('contact_parent_1', 80)->nullable()->after('adresse');
            $table->string('contact_parent_2', 80)->nullable()->after('contact_parent_1');
            $table->string('facebook_profil_url', 500)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('crm_candidates', function (Blueprint $table) {
            $table->dropColumn(['contact_parent_1', 'contact_parent_2', 'facebook_profil_url']);
        });

        Schema::dropIfExists('crm_intakes');
    }
};
