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
        Schema::create('clips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->foreignUuid('episode_id')->nullable()->index();
            $table->string('storage_key')->nullable()->index();
            $table->string('storage_disk')->nullable();
            $table->integer('start_region')->default(0);
            $table->integer('end_region')->default(0);
            $table->string('status')->nullable()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clips');
    }
};
