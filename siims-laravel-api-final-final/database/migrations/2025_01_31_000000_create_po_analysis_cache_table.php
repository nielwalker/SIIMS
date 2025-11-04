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
        Schema::create('po_analysis_cache', function (Blueprint $table) {
            $table->id();
            $table->string('coordinator_id')->nullable();
            $table->string('section_id')->nullable();
            $table->integer('week_number')->nullable();
            $table->string('data_hash', 64); // SHA256 hash of activities + learnings
            $table->json('pos_hit')->nullable(); // Achieved POs
            $table->json('pos_not_hit')->nullable(); // Not achieved POs
            $table->json('po_context_hit')->nullable();
            $table->json('po_word_hit')->nullable();
            $table->json('recommendations')->nullable();
            $table->text('summary')->nullable();
            $table->json('activities')->nullable();
            $table->json('learnings')->nullable();
            $table->timestamps();
            
            // Index for fast lookups (using shorter name to avoid MySQL limit)
            $table->index(['coordinator_id', 'section_id', 'week_number', 'data_hash'], 'po_cache_lookup_idx');
            $table->index('data_hash', 'po_cache_hash_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_analysis_cache');
    }
};

