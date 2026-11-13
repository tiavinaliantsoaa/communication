<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecole_seances', function (Blueprint $table) {
            $table->date('date')->nullable()->after('jour');
        });

        $monday = now()->startOfWeek(Carbon::MONDAY);
        foreach (DB::table('ecole_seances')->whereNull('date')->get() as $seance) {
            $jour = max(1, min(5, (int) $seance->jour));
            DB::table('ecole_seances')->where('id', $seance->id)->update([
                'date' => $monday->copy()->addDays($jour - 1)->toDateString(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('ecole_seances', function (Blueprint $table) {
            $table->dropColumn('date');
        });
    }
};
