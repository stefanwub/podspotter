<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('episode_id')->constrained('episodes')->nullable();
            $table->integer('start_time')->default(0);
            $table->integer('end_time')->default(0);
            $table->text('content')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE sections ADD searchable tsvector NULL');
        DB::statement('CREATE INDEX sections_searchable_index ON sections USING GIN (searchable)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
