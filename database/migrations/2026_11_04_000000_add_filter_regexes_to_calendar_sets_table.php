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
        Schema::table('calendar_sets', function (Blueprint $table) {
            // null = use the default patterns; [] = hide nothing by regex
            $table->json('filter_regexes')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_sets', function (Blueprint $table) {
            $table->dropColumn('filter_regexes');
        });
    }
};
