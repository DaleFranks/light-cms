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
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->boolean('show_site_name');
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('logo_position');
            $table->boolean('sticky_header');
            $table->string('color_primary', 7);
            $table->string('color_secondary', 7);
            $table->string('color_background', 7);
            $table->string('color_text', 7);
            $table->string('color_card_background', 7);
            $table->string('color_header_background', 7);
            $table->string('font_body');
            $table->string('font_headings');
            $table->unsignedSmallInteger('border_radius');
            $table->unsignedSmallInteger('content_width');
            $table->string('footer_style');
            $table->text('custom_css')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
