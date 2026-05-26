<?php

namespace App\Console\Commands;

use App\Models\FixedExpense;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupFixedExpenses extends Command
{
    protected $signature = 'fixed-expenses:cleanup';
    protected $description = '1年以上前に無効化された固定費を物理削除する';

    public function handle(): int
    {
        try {
            $threshold = Carbon::now()->subYear();

            $count = FixedExpense::where('is_active', false)
                ->where('deactivated_at', '<=', $threshold)
                ->delete();

            $this->info("削除完了: {$count}件の無効固定費を物理削除しました");
            Log::info("fixed-expenses:cleanup: {$count}件削除");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("削除処理に失敗しました: {$e->getMessage()}");
            Log::error("fixed-expenses:cleanup 失敗: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
