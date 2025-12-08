<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('guilds', function (Blueprint $table) {
            $table->id();

            // Normalized columns for fast queries/indexes
            $table->string('region', 4);
            $table->string('realm');
            $table->string('name');
            $table->boolean('is_classic')->default(false);

            // Tracking columns for "popular" feature
            $table->unsignedBigInteger('search_count')->default(0);
            $table->timestamp('last_searched_at')->nullable();

            // Flexible payload - entire API response
            $table->json('data')->nullable();
            $table->timestamp('data_fetched_at')->nullable();

            // Job status for async feature
            $table->string('fetch_status', 20)->default('idle');
            $table->timestamp('fetch_queued_at')->nullable();

            $table->timestamps();

            // Composite unique constraint
            $table->unique(['region', 'realm', 'name', 'is_classic']);

            // Indexes for popular queries
            $table->index('search_count');
            $table->index('last_searched_at');
            $table->index('fetch_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guilds');
    }
};
