<?php

namespace Database\Seeders;

use App\Models\Content;
use App\Models\ExpenceCategory;
use App\Models\IncomeCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * chrome-devtools-mcp などでの画面確認用に、
 * ログイン可能なテストアカウントと大量の家計簿データを作成するSeeder
 */
class TestAccountSeeder extends Seeder
{
    public const TEST_EMAIL = 'test-kakep@example.com';

    public const TEST_PASSWORD = 'kakepassword';

    private const TARGET_CONTENT_COUNT = 10000;

    private const MONTHS_RANGE = 24; // 直近24ヶ月に分散させる

    private const INSERT_CHUNK_SIZE = 1000;

    // config('app.expense_contents') と同じ並び順に対応する重み・金額レンジ
    private const EXPENSE_ITEM_SETS = [
        ['weight' => 35, 'items' => [ // 食費
            ['name' => 'コンビニ', 'min' => 300, 'max' => 2000],
            ['name' => 'スーパー', 'min' => 1000, 'max' => 8000],
            ['name' => 'ランチ', 'min' => 600, 'max' => 2000],
            ['name' => '夕食外食', 'min' => 1500, 'max' => 8000],
            ['name' => 'カフェ', 'min' => 300, 'max' => 1000],
            ['name' => '焼肉', 'min' => 3000, 'max' => 10000],
        ]],
        ['weight' => 15, 'items' => [ // 日用品
            ['name' => 'ドラッグストア', 'min' => 500, 'max' => 5000],
            ['name' => 'シャンプー・洗剤', 'min' => 500, 'max' => 3000],
            ['name' => '洗濯洗剤', 'min' => 300, 'max' => 2000],
            ['name' => 'トイレットペーパー', 'min' => 200, 'max' => 1000],
            ['name' => '生活用品', 'min' => 1000, 'max' => 8000],
        ]],
        ['weight' => 5, 'items' => [ // 住居費
            ['name' => '家賃', 'min' => 50000, 'max' => 150000],
            ['name' => '電気代', 'min' => 2000, 'max' => 15000],
            ['name' => 'ガス代', 'min' => 1500, 'max' => 8000],
            ['name' => '水道代', 'min' => 1000, 'max' => 5000],
            ['name' => 'インターネット代', 'min' => 3000, 'max' => 6000],
        ]],
        ['weight' => 10, 'items' => [ // 交際費
            ['name' => '友人と食事', 'min' => 2000, 'max' => 8000],
            ['name' => '同僚ランチ', 'min' => 800, 'max' => 2000],
            ['name' => '飲み会', 'min' => 3000, 'max' => 10000],
            ['name' => 'プレゼント代', 'min' => 1000, 'max' => 10000],
        ]],
        ['weight' => 10, 'items' => [ // 娯楽
            ['name' => '映画', 'min' => 1500, 'max' => 3000],
            ['name' => 'ゲーム購入', 'min' => 500, 'max' => 8000],
            ['name' => 'サブスク（動画）', 'min' => 500, 'max' => 3000],
            ['name' => 'カラオケ', 'min' => 1000, 'max' => 5000],
            ['name' => '書籍', 'min' => 500, 'max' => 3000],
        ]],
        ['weight' => 20, 'items' => [ // 交通費
            ['name' => '電車（通勤）', 'min' => 150, 'max' => 500],
            ['name' => 'タクシー', 'min' => 800, 'max' => 3000],
            ['name' => 'バス', 'min' => 200, 'max' => 500],
            ['name' => '新幹線', 'min' => 3000, 'max' => 20000],
            ['name' => '高速代', 'min' => 1000, 'max' => 5000],
        ]],
        ['weight' => 5, 'items' => [ // 未分類
            ['name' => 'その他', 'min' => 100, 'max' => 5000],
            ['name' => '雑費', 'min' => 100, 'max' => 3000],
        ]],
    ];

    // IncomeCategory::firstCreateData は「給与」カテゴリ1件のみを作成する仕様のため、
    // 収入は単一カテゴリに対して内容(content)のみ多様化させる
    private const INCOME_ITEM_SETS = [
        ['weight' => 100, 'items' => [
            ['name' => '月給', 'min' => 200000, 'max' => 400000],
            ['name' => 'ボーナス', 'min' => 50000, 'max' => 500000],
            ['name' => '副業報酬', 'min' => 5000, 'max' => 100000],
            ['name' => 'フリマ売上', 'min' => 500, 'max' => 20000],
            ['name' => 'お小遣い', 'min' => 3000, 'max' => 30000],
            ['name' => 'キャッシュバック', 'min' => 100, 'max' => 5000],
            ['name' => 'ポイント換金', 'min' => 500, 'max' => 10000],
        ]],
    ];

    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => self::TEST_EMAIL],
            [
                'name' => 'Test User',
                'password' => self::TEST_PASSWORD,
                'email_verified_at' => now(),
            ]
        );

        $expenseCategories = $this->ensureCategories(
            ExpenceCategory::class,
            $user,
            config('app.expense_type_id'),
            'firstCreateData'
        );
        $incomeCategories = $this->ensureCategories(
            IncomeCategory::class,
            $user,
            config('app.income_type_id'),
            'firstCreateData'
        );

        Content::where('user_id', $user->id)->delete();

        $generated = $this->generateContents($user->id, $expenseCategories, $incomeCategories);

        $this->command->info("テストアカウント（{$user->email} / パスワード: ".self::TEST_PASSWORD."）に {$generated} 件の家計簿データを登録しました。");
    }

    /**
     * テストユーザーのカテゴリを config の並び順で作り直し、config順に並んだコレクションを返す
     *
     * 過去の手動テスト等で config と件数が食い違うカテゴリが残っていると
     * INCOME_ITEM_SETS / EXPENSE_ITEM_SETS のインデックス対応がずれるため、
     * 常に削除してから再作成することでテストアカウントの状態を毎回揃える。
     */
    private function ensureCategories(string $modelClass, User $user, int $typeId, string $factoryMethod)
    {
        $modelClass::where('user_id', $user->id)->where('type_id', $typeId)->delete();
        (new $modelClass())->{$factoryMethod}($user);

        return $modelClass::where('user_id', $user->id)->where('type_id', $typeId)->orderBy('filtered_id')->get();
    }

    private function generateContents(int $userId, $expenseCategories, $incomeCategories): int
    {
        $incomeCount = (int) round(self::TARGET_CONTENT_COUNT * 0.12);
        $expenseCount = self::TARGET_CONTENT_COUNT - $incomeCount;

        $rows = [];
        $generated = 0;
        $now = now();

        for ($i = 0; $i < $incomeCount; $i++) {
            $rows[] = $this->buildRow($userId, config('app.income_type_id'), $incomeCategories, self::INCOME_ITEM_SETS, $now);
            $generated++;
            $generated = $this->flushIfNeeded($rows, $generated);
        }

        for ($i = 0; $i < $expenseCount; $i++) {
            $rows[] = $this->buildRow($userId, config('app.expense_type_id'), $expenseCategories, self::EXPENSE_ITEM_SETS, $now);
            $generated++;
            $generated = $this->flushIfNeeded($rows, $generated);
        }

        if (! empty($rows)) {
            Content::insert($rows);
        }

        return $generated;
    }

    private function flushIfNeeded(array &$rows, int $generated): int
    {
        if (count($rows) >= self::INSERT_CHUNK_SIZE) {
            Content::insert($rows);
            $rows = [];
        }

        return $generated;
    }

    private function buildRow(int $userId, int $typeId, $categories, array $itemSets, Carbon $now): array
    {
        $index = $this->weightedRandomIndex(array_column($itemSets, 'weight'));
        $category = $categories[$index] ?? $categories[0];
        $item = $itemSets[$index]['items'][array_rand($itemSets[$index]['items'])];

        return [
            'user_id' => $userId,
            'type_id' => $typeId,
            'category_id' => $category->id,
            'amount' => $this->randomAmount($item['min'], $item['max']),
            'content' => $item['name'],
            'recorded_at' => $this->randomRecordedAt(),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function randomRecordedAt(): string
    {
        $monthsAgo = rand(0, self::MONTHS_RANGE - 1);
        $targetMonth = Carbon::now()->subMonths($monthsAgo)->startOfMonth();
        $day = rand(1, $targetMonth->daysInMonth);

        return $targetMonth->copy()->day($day)->format('Y-m-d 00:00:00');
    }

    private function randomAmount(int $min, int $max): int
    {
        $amount = rand($min, $max);

        if ($amount >= 10000) {
            return (int) (round($amount / 1000) * 1000);
        }
        if ($amount >= 1000) {
            return (int) (round($amount / 100) * 100);
        }

        return (int) (round($amount / 10) * 10);
    }

    private function weightedRandomIndex(array $weights): int
    {
        $sum = array_sum($weights);
        $rand = mt_rand(1, $sum);
        $cumulative = 0;

        foreach ($weights as $index => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $index;
            }
        }

        return array_key_last($weights);
    }
}
