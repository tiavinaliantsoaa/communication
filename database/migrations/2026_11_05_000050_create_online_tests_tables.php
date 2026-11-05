<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('online_tests')) {
            Schema::create('online_tests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('departement_id')->nullable()->constrained('departements')->nullOnDelete();
                $table->string('nom');
                $table->text('description')->nullable();
                $table->unsignedInteger('duree_minutes')->default(45);
                $table->boolean('actif')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('online_test_questions')) {
            Schema::create('online_test_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('online_test_id')->constrained('online_tests')->cascadeOnDelete();
                $table->text('enonce');
                $table->string('type', 32)->default('qcm'); // qcm | texte
                $table->json('options')->nullable();
                $table->string('bonne_reponse')->nullable();
                $table->decimal('points', 8, 2)->default(1);
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('online_test_invitations')) {
            Schema::create('online_test_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('online_test_id')->constrained('online_tests')->cascadeOnDelete();
                $table->foreignId('crm_candidate_id')->nullable()->constrained('crm_candidates')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('token', 64)->unique();
                $table->string('candidat_nom');
                $table->string('candidat_email')->nullable();
                $table->string('statut', 32)->default('en_attente');
                // en_attente | en_cours | termine | annule | expire
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('cancel_reason')->nullable();
                $table->decimal('score_auto', 8, 2)->nullable();
                $table->decimal('score_final', 8, 2)->nullable();
                $table->boolean('valide')->default(false);
                $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('validated_at')->nullable();
                $table->text('commentaire_correcteur')->nullable();
                $table->timestamps();

                $table->index(['online_test_id', 'statut']);
            });
        }

        if (! Schema::hasTable('online_test_answers')) {
            Schema::create('online_test_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('online_test_invitation_id')->constrained('online_test_invitations')->cascadeOnDelete();
                $table->foreignId('online_test_question_id')->constrained('online_test_questions')->cascadeOnDelete();
                $table->text('reponse')->nullable();
                $table->decimal('points_attribues', 8, 2)->nullable();
                $table->timestamps();

                $table->unique(['online_test_invitation_id', 'online_test_question_id'], 'ota_invitation_question_unique');
            });
        }

        if (Schema::hasTable('permissions')) {
            \App\Models\Permission::syncCatalog();

            $keys = [
                'online_tests.view',
                'online_tests.create',
                'online_tests.update',
                'online_tests.delete',
            ];
            $ids = DB::table('permissions')->whereIn('key', $keys)->pluck('id', 'key');
            $crmViewId = DB::table('permissions')->where('key', 'crm.view')->value('id');

            if ($crmViewId) {
                $roleIds = DB::table('permission_role')->where('permission_id', $crmViewId)->pluck('role_id');
                foreach ($roleIds as $roleId) {
                    foreach ($keys as $key) {
                        if (! isset($ids[$key])) {
                            continue;
                        }
                        DB::table('permission_role')->insertOrIgnore([
                            'role_id' => $roleId,
                            'permission_id' => $ids[$key],
                        ]);
                    }
                }

                $userIds = DB::table('permission_user')->where('permission_id', $crmViewId)->pluck('user_id');
                foreach ($userIds as $userId) {
                    foreach (['online_tests.view', 'online_tests.create', 'online_tests.update'] as $key) {
                        if (! isset($ids[$key])) {
                            continue;
                        }
                        DB::table('permission_user')->insertOrIgnore([
                            'user_id' => $userId,
                            'permission_id' => $ids[$key],
                        ]);
                    }
                }
            }
        }

        if (Schema::hasTable('departement_menus')) {
            $now = now();
            foreach (DB::table('departements')->pluck('id') as $departementId) {
                DB::table('departement_menus')->updateOrInsert(
                    ['departement_id' => $departementId, 'menu_key' => 'online_tests'],
                    ['enabled' => true, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('online_test_answers');
        Schema::dropIfExists('online_test_invitations');
        Schema::dropIfExists('online_test_questions');
        Schema::dropIfExists('online_tests');

        if (Schema::hasTable('departement_menus')) {
            DB::table('departement_menus')->where('menu_key', 'online_tests')->delete();
        }

        if (Schema::hasTable('permissions')) {
            $ids = DB::table('permissions')->where('key', 'like', 'online_tests.%')->pluck('id');
            if ($ids->isNotEmpty()) {
                DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
                DB::table('permission_user')->whereIn('permission_id', $ids)->delete();
                DB::table('permissions')->whereIn('id', $ids)->delete();
            }
        }
    }
};
