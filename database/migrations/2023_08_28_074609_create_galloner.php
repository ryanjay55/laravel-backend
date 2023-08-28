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
        Schema::create('galloners', function (Blueprint $table) {
            $table->id('galloners_id');
            $table->foreignId('user_id');
            $table->smallInteger('donate_qty');
            $table->enum('medal', ['none', 'bronze', 'silver', 'gold'])->default('none');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('galloner');
    }
};
