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
        Schema::create('donor_posts', function (Blueprint $table) {
            $table->id('donor_posts_id');
            $table->foreignId('user_id');
            $table->longText('body');
            $table->text('contact');
            $table->smallInteger('isApproved')->default(0);
            $table->smallInteger('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donor_post');
    }
};
