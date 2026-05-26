<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('monthly_amounts');
    }

    public function down(): void
    {
        Schema::create('monthly_amounts', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('amount');
            $table->dateTime('recorded_at');
            $table->timestamps();
        });
    }
};
