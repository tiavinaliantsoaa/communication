<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ecole_etudiants', 'parent_user_id')) {
            return;
        }

        Schema::table('ecole_etudiants', function (Blueprint $table) {
            $table->foreignId('parent_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ecole_etudiants', 'parent_user_id')) {
            return;
        }

        Schema::table('ecole_etudiants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_user_id');
        });
    }
};
