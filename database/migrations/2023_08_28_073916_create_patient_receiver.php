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
        Schema::create('patient_receivers', function (Blueprint $table) {
            $table->id('patient_receivers_id');
            $table->foreignId('user_id');
            $table->text('first_name');
            $table->text('middle_name')->nullable();
            $table->text('last_name'); 
            $table->smallInteger('age');
            $table->enum('blood_type', ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']);
            $table->text('hospital'); 
            $table->decimal('total_amount'); 
            $table->decimal('total_paid'); 
            $table->smallInteger('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_receiver');
    }
};
