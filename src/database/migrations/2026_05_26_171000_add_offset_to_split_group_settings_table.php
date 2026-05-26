<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('split_group_settings', function (Blueprint $table) {
            $table->integer('income_other_offset')->nullable()->after('income_other_ratio');
            $table->integer('expense_other_offset')->nullable()->after('expense_other_ratio');
        });
    }

    public function down(): void
    {
        Schema::table('split_group_settings', function (Blueprint $table) {
            $table->dropColumn(['income_other_offset', 'expense_other_offset']);
        });
    }
};
