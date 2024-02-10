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
        Schema::create('whisper_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('episode_id')->constrained('episodes');
            $table->string('job_id')->nullable()->index();
            $table->text('text')->nullable();
            $table->json('chunks')->nullable();
            $table->string('status')->nullable();
            $table->integer('execution_time')->default(0);
            $table->boolean('locale')->default(true);
            $table->dateTime('succeeded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whisper_jobs');
    }
};
