<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 論理削除済みレコードを物理削除してから deleted_at を除去
        \DB::table('fixed_expenses')->whereNotNull('deleted_at')->delete();

        Schema::table('fixed_expenses', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('fixed_expenses', function (Blueprint $table) {
            $table->dropColumn('deactivated_at');
            $table->softDeletes();
        });
    }
};
