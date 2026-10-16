<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('editorial_events') || Schema::hasColumn('editorial_events', 'urgent')) {
            return;
        }

        Schema::table('editorial_events', function (Blueprint $table) {
            $table->boolean('urgent')->default(false)->after('valide');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('editorial_events') || ! Schema::hasColumn('editorial_events', 'urgent')) {
            return;
        }

        Schema::table('editorial_events', function (Blueprint $table) {
            $table->dropColumn('urgent');
        });
    }
};
