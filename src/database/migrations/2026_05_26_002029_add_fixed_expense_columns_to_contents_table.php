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
        Schema::table('contents', function (Blueprint $table) {
            $table->boolean('is_fixed_expense')->default(false)->after('content');
            $table->unsignedTinyInteger('fixed_expense_day')->nullable()->after('is_fixed_expense');
            $table->unsignedBigInteger('fixed_expense_id')->nullable()->after('fixed_expense_day');
            $table->index('is_fixed_expense', 'idx_contents_is_fixed_expense');
            $table->index('fixed_expense_id', 'idx_contents_fixed_expense_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropIndex('idx_contents_is_fixed_expense');
            $table->dropIndex('idx_contents_fixed_expense_id');
            $table->dropColumn(['is_fixed_expense', 'fixed_expense_day', 'fixed_expense_id']);
        });
    }
};
