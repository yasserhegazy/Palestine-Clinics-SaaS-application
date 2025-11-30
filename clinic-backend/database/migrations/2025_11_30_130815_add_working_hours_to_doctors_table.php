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
        Schema::table('doctors', function (Blueprint $table) {
            $table->time('start_time')->default('09:00:00')->after('clinic_room');
            $table->time('end_time')->default('17:00:00')->after('start_time');
            $table->integer('slot_duration')->default(30)->comment('Duration in minutes')->after('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time', 'slot_duration']);
        });
    }
};
