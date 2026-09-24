<?php

use App\Support\NavbarMenu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departement_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departement_id')->constrained('departements')->cascadeOnDelete();
            $table->string('menu_key');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['departement_id', 'menu_key']);
        });

        $now = now();
        $rows = [];
        $departementIds = DB::table('departements')->pluck('id');

        foreach ($departementIds as $departementId) {
            foreach (NavbarMenu::keys() as $key) {
                $rows[] = [
                    'departement_id' => $departementId,
                    'menu_key' => $key,
                    'enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('departement_menus')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('departement_menus');
    }
};
