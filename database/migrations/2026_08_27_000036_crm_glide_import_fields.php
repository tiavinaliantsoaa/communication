<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_candidates', function (Blueprint $table) {
            $table->string('glide_applicant_id', 80)->nullable()->unique()->after('pipeline_order');
            $table->string('glide_row_id', 80)->nullable()->index()->after('glide_applicant_id');
            $table->boolean('abandon')->default(false)->after('paiement_totalite');
            $table->string('abandon_raison', 255)->nullable()->after('abandon');
        });

        Schema::table('crm_candidate_documents', function (Blueprint $table) {
            $table->string('external_url', 1000)->nullable()->after('path');
            $table->string('glide_document_id', 80)->nullable()->unique()->after('external_url');
            $table->dropUnique('crm_candidate_doc_type_unique');
            $table->index(['crm_candidate_id', 'crm_document_type_id'], 'crm_candidate_doc_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('crm_candidate_documents', function (Blueprint $table) {
            $table->dropIndex('crm_candidate_doc_type_index');
            $table->unique(['crm_candidate_id', 'crm_document_type_id'], 'crm_candidate_doc_type_unique');
            $table->dropColumn(['external_url', 'glide_document_id']);
        });

        Schema::table('crm_candidates', function (Blueprint $table) {
            $table->dropColumn(['glide_applicant_id', 'glide_row_id', 'abandon', 'abandon_raison']);
        });
    }
};
