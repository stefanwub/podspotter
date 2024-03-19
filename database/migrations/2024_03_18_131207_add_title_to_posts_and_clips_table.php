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
        Schema::table('posts', function (Blueprint $table) {
            $table->text('title')->nullable();
        });

        Schema::table('clips', function (Blueprint $table) {
            $table->text('title')->nullable();
            $table->json('subtitles')->nullable();
            $table->string('subtitle_timestamp')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('title');
        });

        Schema::table('clips', function (Blueprint $table) {
            $table->dropColumn('title');
            $table->dropColumn('subtitles');
            $table->dropColumn('subtitle_timestamp');
        });
    }
};
