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
        Schema::create('split_group_category_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('split_group_id')->constrained('split_groups')->onDelete('cascade');
            $table->unsignedBigInteger('category_id');
            $table->unsignedTinyInteger('type_id');
            $table->unsignedTinyInteger('other_ratio');
            $table->timestamps();

            $table->unique(['split_group_id', 'category_id', 'type_id'], 'split_group_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split_group_category_overrides');
    }
};
