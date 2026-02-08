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
        Schema::create('calendar_sources', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Unique identifier (e.g., 'holidays', 'work')
            $table->string('name'); // Display name
            $table->enum('type', ['google', 'ical']); // Calendar type
            $table->text('src'); // Google Calendar ID or iCal URL
            $table->string('color', 7); // Hex color code
            $table->string('emoji', 10)->nullable(); // Optional emoji
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // User ownership
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_sources');
    }
};
