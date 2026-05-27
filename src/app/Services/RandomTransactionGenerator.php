<?php

namespace App\Services;

use App\Models\Content;
use Carbon\Carbon;

class RandomTransactionGenerator
{
    private const INCOME_TYPE_ID  = 1;
    private const EXPENSE_TYPE_ID = 2;

    private const INCOME_CATEGORIES = [
        ['id' => 2, 'items' => [
            ['name' => '月給',             'min' => 200000, 'max' => 400000],
            ['name' => '副業報酬',         'min' => 5000,   'max' => 100000],
            ['name' => 'フリマ売上',       'min' => 500,    'max' => 20000],
            ['name' => 'キャッシュバック', 'min' => 100,    'max' => 5000],
            ['name' => 'ポイント換金',     'min' => 500,    'max' => 10000],
            ['name' => 'ボーナス',         'min' => 50000,  'max' => 500000],
        ]],
    ];

    // weight: カテゴリごとの件数配分の重み（合計100）
    private const EXPENSE_CATEGORIES = [
        ['id' => 1, 'weight' => 35, 'items' => [
            ['name' => 'コンビニ',  'min' => 300,  'max' => 2000],
            ['name' => 'スーパー',  'min' => 1000, 'max' => 8000],
            ['name' => 'ランチ',    'min' => 600,  'max' => 2000],
            ['name' => '夕食外食',  'min' => 1500, 'max' => 8000],
            ['name' => 'カフェ',    'min' => 300,  'max' => 1000],
            ['name' => '焼肉',      'min' => 3000, 'max' => 10000],
        ]],
        ['id' => 2, 'weight' => 15, 'items' => [
            ['name' => 'ドラッグストア',    'min' => 500,  'max' => 5000],
            ['name' => 'シャンプー・洗剤',  'min' => 500,  'max' => 3000],
            ['name' => '洗濯洗剤',         'min' => 300,  'max' => 2000],
            ['name' => 'トイレットペーパー','min' => 200,  'max' => 1000],
            ['name' => '生活用品',         'min' => 1000, 'max' => 8000],
        ]],
        ['id' => 3, 'weight' => 5, 'items' => [
            ['name' => '家賃',           'min' => 50000, 'max' => 150000],
            ['name' => '電気代',         'min' => 2000,  'max' => 15000],
            ['name' => 'ガス代',         'min' => 1500,  'max' => 8000],
            ['name' => '水道代',         'min' => 1000,  'max' => 5000],
            ['name' => 'インターネット代','min' => 3000,  'max' => 6000],
        ]],
        ['id' => 4, 'weight' => 10, 'items' => [
            ['name' => '友人と食事',  'min' => 2000, 'max' => 8000],
            ['name' => '同僚ランチ',  'min' => 800,  'max' => 2000],
            ['name' => '飲み会',      'min' => 3000, 'max' => 10000],
            ['name' => 'プレゼント代','min' => 1000, 'max' => 10000],
        ]],
        ['id' => 5, 'weight' => 10, 'items' => [
            ['name' => '映画',           'min' => 1500, 'max' => 3000],
            ['name' => 'ゲーム購入',     'min' => 500,  'max' => 8000],
            ['name' => 'サブスク（動画）','min' => 500,  'max' => 3000],
            ['name' => 'カラオケ',       'min' => 1000, 'max' => 5000],
            ['name' => '書籍',           'min' => 500,  'max' => 3000],
        ]],
        ['id' => 6, 'weight' => 20, 'items' => [
            ['name' => '電車（通勤）','min' => 150,  'max' => 500],
            ['name' => 'タクシー',   'min' => 800,  'max' => 3000],
            ['name' => 'バス',       'min' => 200,  'max' => 500],
            ['name' => '新幹線',     'min' => 3000, 'max' => 20000],
            ['name' => '高速代',     'min' => 1000, 'max' => 5000],
        ]],
        ['id' => 7, 'weight' => 5, 'items' => [
            ['name' => 'その他','min' => 100, 'max' => 5000],
            ['name' => '雑費',  'min' => 100, 'max' => 3000],
        ]],
    ];

    /**
     * 指定した年月・件数・ユーザーIDでランダムな取引データを生成する。
     *
     * @return int 実際に生成した件数
     */
    public static function generate(int $year, int $month, int $count, int $userId): int
    {
        $daysInMonth  = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $incomeCount  = max(1, (int)round($count * 0.12));
        $expenseCount = $count - $incomeCount;
        $generated    = 0;

        // 収入データを生成
        for ($i = 0; $i < $incomeCount; $i++) {
            $cat  = self::INCOME_CATEGORIES[0];
            $item = $cat['items'][array_rand($cat['items'])];

            Content::create([
                'user_id'     => $userId,
                'type_id'     => self::INCOME_TYPE_ID,
                'category_id' => $cat['id'],
                'amount'      => self::randomAmount($item['min'], $item['max']),
                'content'     => $item['name'],
                'recorded_at' => sprintf('%04d-%02d-%02d 00:00:00', $year, $month, rand(1, $daysInMonth)),
            ]);
            $generated++;
        }

        // 支出データを重みに従って各カテゴリへ配分して生成
        $weights     = array_column(self::EXPENSE_CATEGORIES, 'weight');
        $distributed = self::distributeByWeight($expenseCount, $weights);

        foreach (self::EXPENSE_CATEGORIES as $index => $cat) {
            for ($i = 0; $i < $distributed[$index]; $i++) {
                $item = $cat['items'][array_rand($cat['items'])];

                Content::create([
                    'user_id'     => $userId,
                    'type_id'     => self::EXPENSE_TYPE_ID,
                    'category_id' => $cat['id'],
                    'amount'      => self::randomAmount($item['min'], $item['max']),
                    'content'     => $item['name'],
                    'recorded_at' => sprintf('%04d-%02d-%02d 00:00:00', $year, $month, rand(1, $daysInMonth)),
                ]);
                $generated++;
            }
        }

        return $generated;
    }

    private static function randomAmount(int $min, int $max): int
    {
        $amount = rand($min, $max);

        if ($amount >= 10000) {
            return (int)(round($amount / 1000) * 1000);
        }
        if ($amount >= 1000) {
            return (int)(round($amount / 100) * 100);
        }
        return (int)(round($amount / 10) * 10);
    }

    private static function distributeByWeight(int $total, array $weights): array
    {
        $sumWeights  = array_sum($weights);
        $result      = [];
        $distributed = 0;

        foreach ($weights as $i => $weight) {
            $n          = (int)floor($total * $weight / $sumWeights);
            $result[$i] = $n;
            $distributed += $n;
        }

        // 端数を先頭カテゴリから順に割り当て
        $remaining = $total - $distributed;
        $keys      = array_keys($weights);
        for ($i = 0; $i < $remaining; $i++) {
            $result[$keys[$i % count($keys)]]++;
        }

        return $result;
    }
}
