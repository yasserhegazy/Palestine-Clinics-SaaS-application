<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->string('fingerprint')->nullable()->index();
            $table->text('data');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            $table->index(['notifiable_id', 'created_at']);
            $table->unique(['notifiable_type', 'notifiable_id', 'type', 'fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
