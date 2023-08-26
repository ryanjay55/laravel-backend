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
            $table->foreignId('user_id')->constrained('users', 'user_id');
            // $table->foreignId('user_id')->constrained('users', 'user_id');
            // $table->biginteger('user_id');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');    
            $table->date('dob');     
            $table->string('blood_type');         
            $table->string('occupation');  
            $table->longText('street');            
            $table->string('region');
            $table->string('province');
            $table->string('municipality');
            $table->string('barangay');
            $table->integer('postalcode');
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
