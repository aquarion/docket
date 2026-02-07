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
        Schema::create('calendar_set_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_set_id')->constrained()->onDelete('cascade');
            $table->foreignId('calendar_source_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['calendar_set_id', 'calendar_source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_set_sources');
    }
};
