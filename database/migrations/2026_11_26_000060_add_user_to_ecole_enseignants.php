<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ecole_enseignants') && ! Schema::hasColumn('ecole_enseignants', 'user_id')) {
            Schema::table('ecole_enseignants', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ecole_enseignants') && Schema::hasColumn('ecole_enseignants', 'user_id')) {
            Schema::table('ecole_enseignants', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
