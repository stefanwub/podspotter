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
            $table->integer('gpu')->default(0);

            $table->index(['status', 'server', 'gpu']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whisper_jobs', function (Blueprint $table) {
            $table->dropColumn('gpu');
            $table->dropIndex(['status', 'server', 'gpu']);
        });
    }
};
