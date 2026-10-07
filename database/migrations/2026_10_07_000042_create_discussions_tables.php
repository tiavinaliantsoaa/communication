<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departement_id')->constrained('departements')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nom');
            $table->timestamps();
        });

        Schema::create('discussion_group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_group_id')->constrained('discussion_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->timestamps();
            $table->unique(['discussion_group_id', 'user_id']);
        });

        Schema::create('discussion_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_group_id')->constrained('discussion_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reply_to_id')->nullable()->constrained('discussion_messages')->nullOnDelete();
            $table->text('body')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->index(['discussion_group_id', 'id']);
        });

        Schema::create('discussion_message_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_message_id')->constrained('discussion_messages')->cascadeOnDelete();
            $table->string('path');
            $table->string('nom');
            $table->string('mime')->nullable();
            $table->unsignedInteger('taille')->default(0);
            $table->timestamps();
        });

        if (! Schema::hasTable('permissions')) {
            return;
        }

        \App\Models\Permission::syncCatalog();
        $permissionId = DB::table('permissions')->where('key', 'discussions.view')->value('id');
        if (! $permissionId) {
            return;
        }

        $roleIds = DB::table('permission_role')->distinct()->pluck('role_id');
        foreach ($roleIds as $roleId) {
            DB::table('permission_role')->insertOrIgnore([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }

        $userIds = DB::table('permission_user')->distinct()->pluck('user_id');
        foreach ($userIds as $userId) {
            DB::table('permission_user')->insertOrIgnore([
                'user_id' => $userId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = Schema::hasTable('permissions')
            ? DB::table('permissions')->where('key', 'discussions.view')->value('id')
            : null;

        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permission_user')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('discussion_message_files');
        Schema::dropIfExists('discussion_messages');
        Schema::dropIfExists('discussion_group_user');
        Schema::dropIfExists('discussion_groups');
    }
};
