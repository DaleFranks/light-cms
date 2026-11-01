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
        Schema::table('forms', function (Blueprint $table) {
            $table->json('notify_emails')->nullable()->after('success_message');
        });

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->string('email_status')->nullable()->after('answers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('notify_emails');
        });

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropColumn('email_status');
        });
    }
};
