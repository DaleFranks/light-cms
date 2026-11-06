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
        Schema::create('library_files', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->unique();
            $table->unsignedBigInteger('size');
            $table->string('mime_type');
            $table->timestamp('replaced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('library_files');
    }
};
