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
        Schema::create('user_details', function (Blueprint $table) {
            $table->id('user_details_id');
            $table->foreignId('user_id');
            $table->unsignedInteger('donor_no')->default(0);
            $table->text('first_name');
            $table->text('middle_name')->nullable();
            $table->text('last_name'); 
            $table->enum('sex', ['Male', 'Female']);
            $table->date('dob');     
            $table->text('blood_type');
            $table->text('occupation');  
            $table->longText('street');            
            $table->text('region');
            $table->text('province');
            $table->text('municipality');
            $table->text('barangay');
            $table->integer('postalcode');
            $table->smallInteger('isDeffered')->default(0);
            $table->smallInteger('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
    
};
