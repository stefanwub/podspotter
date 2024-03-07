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
        Schema::create('category_search', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('search_id')->nullable()->index();
            $table->foreignUuid('category_id')->nullable()->index();
            $table->boolean('include')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_search');
    }
};
