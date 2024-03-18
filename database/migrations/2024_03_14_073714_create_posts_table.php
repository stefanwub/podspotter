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
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->foreignUuid('clip_id')->nullable()->index();
            $table->string('thumbnail_storage_key')->nullable()->index();
            $table->string('storage_key')->nullable()->index();
            $table->string('storage_disk')->nullable();
            $table->string('medium')->nullable()->index();
            $table->string('template_name')->nullable();
            $table->json('config')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
