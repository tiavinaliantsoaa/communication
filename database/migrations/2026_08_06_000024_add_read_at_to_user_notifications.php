<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('user_notifications', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('url');
                $table->index(['user_id', 'read_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('user_notifications', 'read_at')) {
                $table->dropIndex(['user_id', 'read_at']);
                $table->dropColumn('read_at');
            }
        });
    }
};
