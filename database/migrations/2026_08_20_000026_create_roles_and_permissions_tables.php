<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('group');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'permission_id']);
        });

        // Seed built-in roles from historical constants
        $now = now();
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Accès total', 'is_system' => true],
            ['name' => 'Administrateur', 'slug' => 'administrateur', 'description' => 'Administration', 'is_system' => true],
            ['name' => 'Responsable Communication', 'slug' => 'responsable_communication', 'description' => null, 'is_system' => true],
            ['name' => 'Gestionnaire Budget', 'slug' => 'gestionnaire_budget', 'description' => null, 'is_system' => true],
            ['name' => 'Stagiaire', 'slug' => 'stagiaire', 'description' => null, 'is_system' => true],
        ];
        foreach ($roles as $role) {
            DB::table('roles')->insert([
                ...$role,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
