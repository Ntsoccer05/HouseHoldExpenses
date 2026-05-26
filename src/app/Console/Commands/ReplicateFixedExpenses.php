<?php

namespace App\Console\Commands;

use App\Models\FixedExpense;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReplicateFixedExpenses extends Command
{
    protected $signature = 'fixed-expenses:replicate {--month= : 対象年月 Ym形式（省略時は当月）}';
    protected $description = '固定費を当月分として複製する';

    public function handle(): int
    {
        $targetDate = $this->option('month')
            ? Carbon::createFromFormat('Ym', $this->option('month'))
            : now();

        $year  = (int)$targetDate->format('Y');
        $month = (int)$targetDate->format('n');

        $this->info("固定費複製開始: {$year}年{$month}月");

        $successCount = 0;
        $skipCount    = 0;
        $errorCount   = 0;

        FixedExpense::where('is_active', true)
            ->chunk(100, function ($fixedExpenses) use ($year, $month, &$successCount, &$skipCount, &$errorCount) {
                foreach ($fixedExpenses as $fixedExpense) {
                    try {
                        $result = $fixedExpense->replicateForMonth($year, $month);
                        $result ? $successCount++ : $skipCount++;
                    } catch (\Exception $e) {
                        $errorCount++;
                        Log::error("固定費複製エラー: id={$fixedExpense->id}, error={$e->getMessage()}");
                    }
                }
            });

        $this->info("完了: 成功={$successCount}, スキップ={$skipCount}, エラー={$errorCount}");
        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
