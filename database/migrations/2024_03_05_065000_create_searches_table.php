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
        Schema::create('searches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained('teams')->nullable();
            $table->string('query')->nullable();
            $table->string('order_by')->nullable();
            $table->dateTime('saved_at')->nullable()->index();
            $table->boolean('alerts')->default(false);
            $table->boolean('older_episode_alerts')->default(false);
            $table->timestamps();

            $table->index(['team_id', 'query']);
            $table->index(['team_id', 'alerts']);
            $table->index(['team_id', 'saved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('searches');
    }
};
