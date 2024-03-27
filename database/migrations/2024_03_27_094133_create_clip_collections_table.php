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
        Schema::create('clip_collection', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('clip_id')->nullable()->index();
            $table->foreignUuid('collection_id')->nullable()->index();
            $table->integer('sort')->default(0)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clip_collection');
    }
};
