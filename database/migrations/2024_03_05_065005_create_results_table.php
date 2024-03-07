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
        Schema::create('results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('search_id')->nullable()->index();
            $table->foreignUuid('episode_id')->nullable()->index();
            $table->string('query')->nullable()->index();
            $table->integer('order')->default(0)->index();
            $table->boolean('alert')->default(false)->index();
            $table->json('sections')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('indexed_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->index(['search_id', 'indexed_at']);
            $table->index(['search_id', 'published_at']);
            $table->index(['search_id', 'alert']);
            $table->index(['created_at', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
