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
        Schema::create('episodes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('show_id')->constrained('shows')->nullable();
            $table->string('podcast_index_id')->nullable()->index();
            $table->string('guid')->nullable()->index();
            $table->string('title')->nullable()->index();
            $table->text('description')->nullable();
            $table->integer('episode')->nullable();
            $table->integer('season')->nullable();
            $table->text('enclosure_url')->nullable();
            $table->string('enclosure_type')->nullable();
            $table->string('enclosure_length')->nullable();
            $table->integer('duration')->default(0);
            $table->string('image_url')->nullable();
            $table->integer('sections_count')->nullable();
            $table->string('status')->nullable()->index();
            $table->dateTime('transcribed_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->index(['show_id', 'season', 'episode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
