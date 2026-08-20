<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracked_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nom');
            $table->string('destination_url', 2048);
            $table->string('slug', 100)->unique();
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('tracked_link_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracked_link_id')->constrained('tracked_links')->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('device', 30)->nullable();
            $table->string('os', 60)->nullable();
            $table->string('browser', 60)->nullable();
            $table->string('referer', 2048)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tracked_link_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracked_link_visits');
        Schema::dropIfExists('tracked_links');
    }
};
