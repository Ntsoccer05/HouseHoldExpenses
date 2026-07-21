<?php

namespace App\Models;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contentモデルクラス
 *
 * 収入・支出に関する情報を管理するモデル
 */
class Content extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type_id',
        'category_id',
        'amount',
        'content',
        'recorded_at',
        'is_fixed_expense',
        'fixed_expense_id',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    /**
     * Userモデルとのリレーション（多対1）
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Typeモデルとのリレーション（多対1）
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * type_id + category_id ごとのカテゴリ表示情報(名前/アイコン)のリクエスト内キャッシュ
     *
     * formatedTransaction() は一覧取得時に取引1件ごとに呼ばれるため、
     * キャッシュせず毎回クエリすると件数分のN+1が発生する。
     *
     * @var array<string, array{0: string|null, 1: string|null}>
     */
    protected static array $categoryCache = [];

    /**
     * カテゴリ表示情報のリクエスト内キャッシュをクリアする
     *
     * phpunit実行中は1プロセス内で全テストが動くため static プロパティが
     * テストをまたいで残ってしまう。AppServiceProvider::boot() から毎リクエスト呼び出す。
     */
    public static function resetCategoryCache(): void
    {
        self::$categoryCache = [];
    }

    /**
     * カテゴリをデータベースに挿入
     *
     * 与えられたデータとタイプIDに基づいて適切なカテゴリを設定します。
     *
     * @param  array  $data  カテゴリ情報を含むデータ
     * @param  Content  $transactionContent  タイプID（収入または支出を識別）
     * @return void
     */
    public function insertCategory($data, $transactionContent)
    {
        if ($transactionContent->type_id === config('app.expense_type_id')) {
            $expenseCategory = new ExpenceCategory();
            $this->category_id = $expenseCategory->where('user_id', $transactionContent->user_id)->where('content', $data['category'])->value('id');
        } elseif ($transactionContent->type_id === config('app.income_type_id')) {
            $incomeCategory = new IncomeCategory();
            $this->category_id = $incomeCategory->where('user_id', $transactionContent->user_id)->where('content', $data['category'])->value('id');
        }
    }

    /**
     * トランザクションデータを整形
     *
     * 指定されたコンテンツデータをフォーマットして返します。
     *
     * @param  Content  $content  トランザクションデータ
     * @return array 整形されたトランザクションデータ
     */
    public function formatedTransaction($content)
    {
        // type_id から直接判定（types テーブルのリレーションに依存しない）
        $typeId = $content->type_id;
        if ($typeId == config('app.income_type_id')) {
            $type_name = 'income';
        } elseif ($typeId == config('app.expense_type_id')) {
            $type_name = 'expense';
        } else {
            return null;
        }

        [$category_name, $category_icon] = $this->resolveCategoryDisplay($typeId, $content->category_id);
        $formattedRecordedDay = (new DateTime($content->recorded_at))->format('Y-m-d');

        return [
            'id' => $content->id,
            'date' => $formattedRecordedDay,
            'amount' => $content->amount,
            'content' => $content->content,
            'type' => $type_name,
            'category' => $category_name,
            'icon' => $category_icon,
            'isFixedExpense' => (bool) ($content->is_fixed_expense ?? false),
            'fixedExpenseId' => $content->fixed_expense_id,
        ];
    }

    /**
     * カテゴリの表示情報(名前・アイコン)を取得
     *
     * type_id + category_id 単位でキャッシュし、同一リクエスト内で
     * 同じカテゴリに対するクエリが繰り返されないようにする。
     *
     * @param  int  $typeId
     * @param  int|null  $categoryId
     * @return array{0: string|null, 1: string|null} [category_name, category_icon]
     */
    private function resolveCategoryDisplay($typeId, $categoryId): array
    {
        if (is_null($categoryId)) {
            return [null, null];
        }

        $cacheKey = $typeId.':'.$categoryId;
        if (! array_key_exists($cacheKey, self::$categoryCache)) {
            if ($typeId === config('app.expense_type_id')) {
                $category = ExpenceCategory::select('content', 'icon')->find($categoryId);
            } elseif ($typeId === config('app.income_type_id')) {
                $category = IncomeCategory::select('content', 'icon')->find($categoryId);
            } else {
                $category = null;
            }
            self::$categoryCache[$cacheKey] = $category ? [$category->content, $category->icon] : [null, null];
        }

        return self::$categoryCache[$cacheKey];
    }

    /**
     * "Ym" 形式の年月文字列から、月初〜月末の日時範囲を返す
     *
     * DATE_FORMAT(recorded_at, "%Y%m") = ? のような列を関数で包む条件は
     * recorded_at のインデックスを使えず該当ユーザーの全行を走査してしまうため、
     * whereBetween で使える範囲に変換する。
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function monthRange(string $yearMonth): array
    {
        $start = Carbon::createFromFormat('Ym', $yearMonth)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [$start, $end];
    }

    /**
     * "Y" 形式の年文字列から、年初〜年末の日時範囲を返す（monthRange と同じ理由）
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function yearRange(string $year): array
    {
        $start = Carbon::createFromFormat('Y', $year)->startOfYear();
        $end = $start->copy()->endOfYear();

        return [$start, $end];
    }

    /**
     * 月次トランザクションデータを取得
     *
     * 指定された月のトランザクションデータを整形して返します。
     *
     * @param  object  $request  リクエストデータ（currentMonthを含む）
     * @return array 整形された月次データ
     */
    public function getMonthlyTransaction($request)
    {
        $monthlyData = [];
        [$start, $end] = $this->monthRange($request->currentMonth);
        $data = $this::where('user_id', $request->user_id)->whereBetween('recorded_at', [$start, $end])->get();
        foreach ($data as $index => $content) {
            $formatedTransaction = $this->formatedTransaction($content);
            if ($formatedTransaction !== null) {
                $monthlyData[] = $formatedTransaction;
            }
        }

        return $monthlyData;
    }

    /**
     * 指定月・ユーザーIDでトランザクションデータを取得（一括取得用）
     *
     * @param  int  $userId  ユーザーID
     * @param  string  $yearMonth  年月（Ym形式: "202604"）
     * @return array 整形されたトランザクションデータ
     */
    public function getMonthlyTransactionForMonth($userId, $yearMonth)
    {
        $monthlyData = [];
        [$start, $end] = $this->monthRange($yearMonth);
        $data = $this::where('user_id', $userId)
            ->whereBetween('recorded_at', [$start, $end])
            ->get();
        foreach ($data as $content) {
            $formatedTransaction = $this->formatedTransaction($content);
            if ($formatedTransaction !== null) {
                $monthlyData[] = $formatedTransaction;
            }
        }

        return $monthlyData;
    }

    /**
     * 前月次トランザクションデータを取得
     *
     * 指定された前月のトランザクションデータを整形して返します。
     *
     * @param  object  $request  リクエストデータ（currentMonthを含む）
     * @return array 整形された月次データ
     */
    public function getPreMonthlyTransaction($request)
    {
        $monthlyData = [];
        $selectedMonth = Carbon::createFromFormat('Ym', $request->currentMonth);
        $preMonth = $selectedMonth->copy()->subMonth()->format('Ym');
        [$start, $end] = $this->monthRange($preMonth);
        $data = $this::where('user_id', $request->user_id)->whereBetween('recorded_at', [$start, $end])->get();
        foreach ($data as $index => $content) {
            $formatedTransaction = $this->formatedTransaction($content);
            $monthlyData[] = $formatedTransaction;
        }

        return $monthlyData;
    }

    /**
     * 年次トランザクションデータを取得
     *
     * 指定された年のトランザクションデータを整形して返します。
     *
     * @param  object  $request  リクエストデータ（currentYearを含む）
     * @return array 整形された年次データ
     */
    public function getYearlyTransaction($request)
    {
        $yearlyData = [];
        [$start, $end] = $this->yearRange($request->currentYear);
        $data = $this::where('user_id', $request->user_id)->whereBetween('recorded_at', [$start, $end])->get();
        foreach ($data as $index => $content) {
            $formatedTransaction = $this->formatedTransaction($content);
            $yearlyData[] = $formatedTransaction;
        }

        return $yearlyData;
    }

    /**
     * 前年次トランザクションデータを取得
     *
     * 指定された前年のトランザクションデータを整形して返します。
     *
     * @param  object  $request  リクエストデータ（currentYearを含む）
     * @return array 整形された年次データ
     */
    public function getPreYearlyTransaction($request)
    {
        $yearlyData = [];
        $selectedYear = Carbon::createFromFormat('Y', $request->currentYear);
        $preYear = $selectedYear->copy()->subYear()->format('Y');
        [$start, $end] = $this->yearRange($preYear);
        $data = $this::where('user_id', $request->user_id)->whereBetween('recorded_at', [$start, $end])->get();
        foreach ($data as $index => $content) {
            $formatedTransaction = $this->formatedTransaction($content);
            $yearlyData[] = $formatedTransaction;
        }

        return $yearlyData;
    }

    /**
     * カテゴリを削除
     *
     * @param  ExpenceCategory|IncomeCategory  $userCategory
     */
    public static function deleteContentsByCategory($userCategory)
    {
        //static メソッド内では $this は使えない
        self::where('category_id', $userCategory->id)->where('type_id', $userCategory->type_id)->delete();
    }
}
