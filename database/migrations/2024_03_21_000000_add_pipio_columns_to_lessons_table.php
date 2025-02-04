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
        Schema::table('lessons', function (Blueprint $table) {
            $table->text('pipio_project_id')->nullable();
            $table->text('pipio_video_id')->nullable();
            $table->text('pipio_status')->nullable();
            $table->text('video_url')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->boolean('is_pipio_processed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('pipio_project_id');
            $table->dropColumn('pipio_video_id');
            $table->dropColumn('pipio_status');
            $table->dropColumn('video_url');
            $table->dropColumn('thumbnail_url');
            $table->dropColumn('is_pipio_processed');
        });
    }
}; 