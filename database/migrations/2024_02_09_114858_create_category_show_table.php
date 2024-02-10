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
        Schema::create('category_show', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('category_id')->constrained('categories')->nullable();
            $table->foreignUuid('show_id')->constrained('shows')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_show');
    }
};
