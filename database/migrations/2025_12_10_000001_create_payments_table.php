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
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('received_by')->nullable(); // Secretary/Reception who collected the payment
            
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0); // For partial payments
            $table->enum('payment_method', ['Cash', 'Later', 'Partial', 'Exempt'])->default('Cash');
            $table->enum('status', ['Paid', 'Pending', 'Partial', 'Exempt', 'Refunded'])->default('Pending');
            
            $table->timestamp('payment_date')->nullable();
            $table->string('receipt_number')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->string('exemption_reason')->nullable(); // For charity/free cases
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('appointment_id')->references('appointment_id')->on('appointments')->onDelete('cascade');
            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
            $table->foreign('clinic_id')->references('clinic_id')->on('clinics')->onDelete('cascade');
            $table->foreign('received_by')->references('user_id')->on('users')->onDelete('set null');

            // Indexes for common queries
            $table->index(['clinic_id', 'status']);
            $table->index(['clinic_id', 'payment_date']);
            $table->index(['patient_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
