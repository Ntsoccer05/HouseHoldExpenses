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
        Schema::create('split_group_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('split_group_id')->constrained('split_groups')->onDelete('cascade');
            $table->unsignedTinyInteger('income_other_ratio')->nullable();
            $table->unsignedTinyInteger('expense_other_ratio')->nullable();
            $table->timestamps();

            $table->unique('split_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split_group_settings');
    }
};
