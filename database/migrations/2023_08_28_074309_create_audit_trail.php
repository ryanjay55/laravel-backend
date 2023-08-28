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
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id('audit_trails_id');
            $table->foreignId('user_id');
            $table->text('action');
            $table->enum('status', ['success', 'failed']);
            $table->string('ip_address');
            $table->text('region');
            $table->text('city');
            $table->string('postal');
            $table->decimal('latitude'); 
            $table->decimal('longitude'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trail');
    }
};
