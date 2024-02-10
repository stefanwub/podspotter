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
        Schema::create('shows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('feed_url')->nullable()->index();
            $table->string('image_url')->nullable();
            $table->string('author')->nullable();
            $table->string('email')->nullable();
            $table->string('language')->nullable()->index();
            $table->string('guid')->nullable()->index();
            $table->string('medium')->nullable()->index();
            $table->string('podcast_index_id')->nullable()->index();
            $table->string('spotify_id')->nullable()->index();
            $table->integer('ranking')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shows');
    }
};
