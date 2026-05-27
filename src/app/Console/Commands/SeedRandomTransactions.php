<?php

namespace App\Console\Commands;

use App\Services\RandomTransactionGenerator;
use Illuminate\Console\Command;

class SeedRandomTransactions extends Command
{
    protected $signature = 'seed:transactions
                            {--year=  : 対象年 (例: 2026)。省略すると対話入力}
                            {--month= : 対象月 (例: 4)。省略すると対話入力}
                            {--count=50 : 生成するデータ件数 (デフォルト: 50)}
                            {--user=11  : 対象ユーザーID (デフォルト: 11)}';

    protected $description = '指定した年月・件数でランダムな取引データを生成します'
        . "\n\n  使い方:"
        . "\n    php artisan seed:transactions --year=2026 --month=4 --count=50"
        . "\n    php artisan seed:transactions --year=2025 --month=12 --count=100 --user=11"
        . "\n    php artisan seed:transactions  ← 引数省略で対話入力モード";

    public function handle(): int
    {
        $year  = $this->option('year')
            ? (int)$this->option('year')
            : (int)$this->ask('対象年を入力してください', now()->year);

        $month = $this->option('month')
            ? (int)$this->option('month')
            : (int)$this->ask('対象月を入力してください', now()->month);

        $count  = (int)$this->option('count');
        $userId = (int)$this->option('user');

        if ($year < 2000 || $year > 2099) {
            $this->error('年が不正です（2000〜2099 の範囲で指定してください）');
            return Command::FAILURE;
        }
        if ($month < 1 || $month > 12) {
            $this->error('月が不正です（1〜12 の範囲で指定してください）');
            return Command::FAILURE;
        }
        if ($count < 1 || $count > 10000) {
            $this->error('件数が不正です（1〜10000 の範囲で指定してください）');
            return Command::FAILURE;
        }

        $this->info("▶ {$year}年{$month}月のランダム取引データを {$count} 件生成します（user_id={$userId}）");

        $generated = RandomTransactionGenerator::generate($year, $month, $count, $userId);

        $this->info("{$generated} 件の取引データを登録しました。");

        return Command::SUCCESS;
    }
}
