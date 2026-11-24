<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editorial_events', function (Blueprint $table) {
            $table->string('reel_path')->nullable()->after('visuel_nom');
            $table->string('reel_nom')->nullable()->after('reel_path');
        });
    }

    public function down(): void
    {
        Schema::table('editorial_events', function (Blueprint $table) {
            $table->dropColumn(['reel_path', 'reel_nom']);
        });
    }
};
