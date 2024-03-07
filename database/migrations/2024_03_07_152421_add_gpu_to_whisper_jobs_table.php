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
        Schema::table('whisper_jobs', function (Blueprint $table) {
            $table->foreignUuid('gpu_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whisper_jobs', function (Blueprint $table) {
            $table->dropIndex(['gpu_id']);
            $table->dropColumn('gpu_id');
        });
    }
};
