<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. A site that hid its name next to the logo keeps it hidden.
     */
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->string('site_name_position')->default('beside')->after('site_name');
        });

        DB::table('themes')->where('show_site_name', false)->update(['site_name_position' => 'hidden']);

        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn('show_site_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->boolean('show_site_name')->default(true)->after('site_name');
        });

        DB::table('themes')->where('site_name_position', 'hidden')->update(['show_site_name' => false]);

        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn('site_name_position');
        });
    }
};
