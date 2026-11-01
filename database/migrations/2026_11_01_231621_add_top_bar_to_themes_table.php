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
        Schema::table('themes', function (Blueprint $table) {
            $table->string('top_bar_text')->nullable();
            $table->string('top_bar_style')->default('dark');
            $table->string('top_bar_alignment')->default('center');
            $table->boolean('top_bar_stays_visible')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn(['top_bar_text', 'top_bar_style', 'top_bar_alignment', 'top_bar_stays_visible']);
        });
    }
};
