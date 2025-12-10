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
        Schema::table('appointments', function (Blueprint $table) {
            $table->decimal('fee_amount', 10, 2)->nullable()->after('notes');
            $table->enum('payment_status', ['Pending', 'Paid', 'Partial', 'Exempt'])->default('Pending')->after('fee_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['fee_amount', 'payment_status']);
        });
    }
};
