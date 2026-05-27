<?php

namespace Database\Seeders;

use App\Services\RandomTransactionGenerator;
use Illuminate\Database\Seeder;

class April2026TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $generated = RandomTransactionGenerator::generate(
            year:   2026,
            month:  4,
            count:  50,
            userId: 11,
        );

        $this->command->info("{$generated}件の2026年4月取引データを登録しました。");
    }
}
