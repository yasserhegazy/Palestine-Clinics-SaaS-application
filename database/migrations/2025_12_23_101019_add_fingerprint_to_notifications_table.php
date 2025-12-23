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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('fingerprint')->nullable()->index()->after('notifiable_id');
            
            // Add unique constraint for deduplication
            $table->unique(['notifiable_type', 'notifiable_id', 'type', 'fingerprint'], 'notifications_unique_fingerprint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropUnique('notifications_unique_fingerprint');
            $table->dropColumn('fingerprint');
        });
    }
};
