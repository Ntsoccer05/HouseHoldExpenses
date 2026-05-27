<?php

namespace Database\Seeders;

use App\Models\Content;
use Illuminate\Database\Seeder;

class May2026TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $userId        = 11;
        $incomeTypeId  = 1;
        $expenseTypeId = 2;

        // income category: 給与(id=2)
        // expense categories: 食費(1), 日用品(2), 住居費(3), 交際費(4), 娯楽(5), 交通費(6), 未分類(7)

        $transactions = [
            // 収入 (5件)
            ['type_id' => $incomeTypeId,  'category_id' => 2, 'amount' => 280000, 'content' => '5月給与',          'date' => '2026-05-25'],
            ['type_id' => $incomeTypeId,  'category_id' => 2, 'amount' => 50000,  'content' => '副業報酬',          'date' => '2026-05-15'],
            ['type_id' => $incomeTypeId,  'category_id' => 2, 'amount' => 5000,   'content' => 'フリマ売上',        'date' => '2026-05-10'],
            ['type_id' => $incomeTypeId,  'category_id' => 2, 'amount' => 3000,   'content' => 'キャッシュバック',   'date' => '2026-05-20'],
            ['type_id' => $incomeTypeId,  'category_id' => 2, 'amount' => 8000,   'content' => 'ポイント換金',       'date' => '2026-05-05'],

            // 食費 (15件)
            ['type_id' => $expenseTypeId, 'category_id' => 1, 'amount' => 1200,  'content' => 'コンビニ',           'date' => '2026-05-01'],
            ['type_id' => $expenseTypeId, 'category_id' => 1, 'amount' => 3500,  'content' => 'スーパー',           'date' => '2026-05-02'],
            ['type_id' => $expenseTypeId, 'category_id' => 1, 'amount' => 850,   'content' => 'ランチ',             'date' => '2026-05-03'],
            ['type_id' => $expenseTypeId, 'category_id' => 1, 'amount' => 4200,  'content' => '夕食外食',           'date' => '2026-05-05'],
            ['type_id' => $expenseTypeId, 'category_id' => 1, 'amount' => 2800,  'content' => 'スーパー',           'date' => '2026-05-07'],
            ['type_id' => $expenseTypeId, 'category_id' => 1, 'amount' => 680,   'content' => 'コンビニ朝食',       'date' => '2026-05-09'],
            ['type_id' => $expenseTypeId, 'category_id' => 1, 'amount' => 1500,  'content' => 'ランチ',             'date' => '2026-05-12'],
            ['type_id' => $expenseTypeId, 'category_id' => 1, 'amount' => 3200,  'content' => 'スーパー',           'date' => '2026-05-14'],
            ['type_id' => $expenseTypeId, 'category_id' => 1, 'amount' => 5600,  'content' => '焼肉',               'date' => '2026-05-17'],
            ['type_id' => $expenseTypeId, 'category_id' => 1, 'amount' => 920,   'content' => 'コンビニ',           'date' => '2026-05-19'],
            ['type_id' => $expenseTypeId, 'category_id' => 1, 'amount' => 2400,  'content' => 'スーパー',           'date' => '2026-05-21'],
            ['type_id' => $expenseTypeId, 'category_id' => 1, 'amount' => 1100,  'content' => 'ランチ',             'date' => '2026-05-23'],
            ['type_id' => $expenseTypeId, 'category_id' => 1, 'amount' => 3800,  'content' => 'スーパー週末',       'date' => '2026-05-24'],
            ['type_id' => $expenseTypeId, 'category_id' => 1, 'amount' => 780,   'content' => 'コンビニ',           'date' => '2026-05-27'],
            ['type_id' => $expenseTypeId, 'category_id' => 1, 'amount' => 2600,  'content' => 'スーパー',           'date' => '2026-05-30'],

            // 日用品 (7件)
            ['type_id' => $expenseTypeId, 'category_id' => 2, 'amount' => 1800,  'content' => 'ドラッグストア',     'date' => '2026-05-03'],
            ['type_id' => $expenseTypeId, 'category_id' => 2, 'amount' => 3200,  'content' => 'シャンプー・洗剤',   'date' => '2026-05-06'],
            ['type_id' => $expenseTypeId, 'category_id' => 2, 'amount' => 650,   'content' => 'トイレットペーパー', 'date' => '2026-05-11'],
            ['type_id' => $expenseTypeId, 'category_id' => 2, 'amount' => 2100,  'content' => 'ドラッグストア',     'date' => '2026-05-16'],
            ['type_id' => $expenseTypeId, 'category_id' => 2, 'amount' => 4500,  'content' => '生活用品まとめ買い', 'date' => '2026-05-22'],
            ['type_id' => $expenseTypeId, 'category_id' => 2, 'amount' => 980,   'content' => '洗濯洗剤',           'date' => '2026-05-26'],
            ['type_id' => $expenseTypeId, 'category_id' => 2, 'amount' => 1350,  'content' => 'ドラッグストア',     'date' => '2026-05-29'],

            // 住居費 (3件)
            ['type_id' => $expenseTypeId, 'category_id' => 3, 'amount' => 75000, 'content' => '家賃',               'date' => '2026-05-01'],
            ['type_id' => $expenseTypeId, 'category_id' => 3, 'amount' => 8500,  'content' => '電気代',             'date' => '2026-05-10'],
            ['type_id' => $expenseTypeId, 'category_id' => 3, 'amount' => 4200,  'content' => 'ガス代',             'date' => '2026-05-15'],

            // 交際費 (5件)
            ['type_id' => $expenseTypeId, 'category_id' => 4, 'amount' => 4500,  'content' => '友人と食事',         'date' => '2026-05-04'],
            ['type_id' => $expenseTypeId, 'category_id' => 4, 'amount' => 3200,  'content' => '同僚ランチ',         'date' => '2026-05-13'],
            ['type_id' => $expenseTypeId, 'category_id' => 4, 'amount' => 6800,  'content' => '飲み会',             'date' => '2026-05-18'],
            ['type_id' => $expenseTypeId, 'category_id' => 4, 'amount' => 2500,  'content' => 'プレゼント代',       'date' => '2026-05-24'],
            ['type_id' => $expenseTypeId, 'category_id' => 4, 'amount' => 5200,  'content' => '友人と外食',         'date' => '2026-05-31'],

            // 娯楽 (5件)
            ['type_id' => $expenseTypeId, 'category_id' => 5, 'amount' => 1800,  'content' => '映画',               'date' => '2026-05-04'],
            ['type_id' => $expenseTypeId, 'category_id' => 5, 'amount' => 3500,  'content' => 'ゲーム購入',         'date' => '2026-05-08'],
            ['type_id' => $expenseTypeId, 'category_id' => 5, 'amount' => 2200,  'content' => 'サブスク（動画）',   'date' => '2026-05-11'],
            ['type_id' => $expenseTypeId, 'category_id' => 5, 'amount' => 4800,  'content' => 'カラオケ',           'date' => '2026-05-17'],
            ['type_id' => $expenseTypeId, 'category_id' => 5, 'amount' => 1200,  'content' => '書籍',               'date' => '2026-05-28'],

            // 交通費 (10件)
            ['type_id' => $expenseTypeId, 'category_id' => 6, 'amount' => 500,   'content' => '電車（通勤）',       'date' => '2026-05-01'],
            ['type_id' => $expenseTypeId, 'category_id' => 6, 'amount' => 500,   'content' => '電車（通勤）',       'date' => '2026-05-06'],
            ['type_id' => $expenseTypeId, 'category_id' => 6, 'amount' => 1200,  'content' => 'タクシー',           'date' => '2026-05-08'],
            ['type_id' => $expenseTypeId, 'category_id' => 6, 'amount' => 500,   'content' => '電車（通勤）',       'date' => '2026-05-13'],
            ['type_id' => $expenseTypeId, 'category_id' => 6, 'amount' => 500,   'content' => '電車（通勤）',       'date' => '2026-05-15'],
            ['type_id' => $expenseTypeId, 'category_id' => 6, 'amount' => 500,   'content' => '電車（通勤）',       'date' => '2026-05-20'],
            ['type_id' => $expenseTypeId, 'category_id' => 6, 'amount' => 2400,  'content' => '新幹線',             'date' => '2026-05-22'],
            ['type_id' => $expenseTypeId, 'category_id' => 6, 'amount' => 500,   'content' => '電車（通勤）',       'date' => '2026-05-26'],
            ['type_id' => $expenseTypeId, 'category_id' => 6, 'amount' => 800,   'content' => 'バス',               'date' => '2026-05-28'],
            ['type_id' => $expenseTypeId, 'category_id' => 6, 'amount' => 500,   'content' => '電車（通勤）',       'date' => '2026-05-29'],
        ];

        foreach ($transactions as $t) {
            Content::create([
                'user_id'     => $userId,
                'type_id'     => $t['type_id'],
                'category_id' => $t['category_id'],
                'amount'      => $t['amount'],
                'content'     => $t['content'],
                'recorded_at' => $t['date'] . ' 00:00:00',
            ]);
        }

        $this->command->info('50件の2026年5月取引データを登録しました。');
    }
}
