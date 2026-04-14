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
            // 複合インデックス: user_id と recorded_at でクエリを高速化
            // 月次トランザクション取得時のパフォーマンス向上
            $table->index(['user_id', 'recorded_at'], 'idx_user_recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropIndex('idx_user_recorded_at');
        });
    }
};
