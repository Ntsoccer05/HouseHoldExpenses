<?php

namespace App\Http\Controllers;

use App\Enums\TypeEnum;
use App\Http\Requests\TransactionRequest;
use App\Http\Requests\CopyMultipleContentsRequest;
use App\Models\Content;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;
use Illuminate\Http\Response;
use Carbon\Carbon;

/**
 * 家計簿管理用コントローラー
 * 
 * 家計簿の作成、更新、削除、取得を行うAPIエンドポイントを提供します。
 */
class TransactionController extends Controller
{
    /**
     * 家計簿一覧を取得
     *
     * @param \Illuminate\Http\Request $request HTTPリクエストオブジェクト。リクエストには、ユーザーIDとカテゴリデータが含まれています。
     * @param \App\Models\IncomeCategory $incomeCategory 収入カテゴリモデルインスタンス。
     * @return \Illuminate\Http\JsonResponse カテゴリ作成の結果をJSON形式で返します。
     */
    public function index(Request $request, Content $transactionContent, Type $type){
        $user_id = $request->user_id;
        $transactionContents = Content::with(['type'])->where('user_id', $user_id)->get();
        $transactions = [];
        foreach($transactionContents as $index => $content){
            $formatedTransaction = $content->formatedTransaction($content);
            $transactions[] = $formatedTransaction;
        }

        return response()->json(['message' => '家計簿を登録しました', 'transactions' => $transactions], 200);
    }

    /**
     * 新しい家計簿作成
     *
     * @param \Illuminate\Http\Request $request HTTPリクエストオブジェクト。リクエストには、ユーザーIDとカテゴリデータが含まれています。
     * @param \App\Models\Content $transactionContent 家計簿モデルのインスタンス。
     * @param \App\Models\Type $type タイプモデルのインスタンス。
     * @return \Illuminate\Http\JsonResponse カテゴリ作成の結果をJSON形式で返します。
     */
    public function create(TransactionRequest $request, Content $transactionContent){
        $user_id = $request->user_id;
        $contents = $request->transaction;
        try {
            // Start a new database transaction
            DB::beginTransaction();
            $transactionContent->user_id = $user_id;
            $transactionContent->type_id = TypeEnum::fromLabel($contents['type'])?->value;
            $transactionContent->insertCategory($contents, $transactionContent);
            $transactionContent->recorded_at = new DateTime($contents['date']);
            $transactionContent->amount = $contents['amount'];
            $transactionContent->content = $contents['content'];
            $transactionContent->save();
            // Commit the transaction
            DB::commit();

            return response()->json(['message' => '家計簿を登録しました', 'id' => $transactionContent->id], 200);
        } catch (\Exception $e) {
            // Roll back the transaction if there's an error
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 既存の家計簿を更新
     *
     * @param \Illuminate\Http\Request $request HTTPリクエストオブジェクト。リクエストには、ユーザーIDとカテゴリデータが含まれています。
     * @param \App\Models\IncomeCategory $incomeCategory 収入カテゴリモデルインスタンス。
     * @return \Illuminate\Http\JsonResponse カテゴリ作成の結果をJSON形式で返します。
     */
    public function update(TransactionRequest $request, Content $transactionContent, Type $type){
        $user_id = $request->user_id;
        $contents = $request->transaction;
        $transactionContent = $transactionContent->where('user_id', $user_id)->where('id', $request->transactionId)->first();
        if($transactionContent){
            try {
                // Start a new database transaction
                DB::beginTransaction();
                $transactionContent->type_id = TypeEnum::fromLabel($contents['type'])?->value;
                $transactionContent->insertCategory($contents, $transactionContent);
                $transactionContent->recorded_at = new DateTime($contents['date']);
                $transactionContent->amount = $contents['amount'];
                $transactionContent->content = $contents['content'];
                $transactionContent->save();
                // // Commit the transaction
                DB::commit();
    
                return response()->json(['message' => '家計簿を登録しました', 'id' => $transactionContent->id], 200);
            } catch (\Exception $e) {
                // Roll back the transaction if there's an error
                DB::rollBack();
    
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }
    }

    /**
     * 家計簿を削除
     *
     * 指定されたIDの家計簿データを削除します。
     *
     * @param Request $request ユーザーIDとトランザクションIDを含むリクエスト。
     * @param Content $transactionContent 家計簿モデルのインスタンス。
     * @return \Illuminate\Http\JsonResponse 削除結果をJSON形式で返します。
     */
    public function delete(Request $request, Content $transactionContent){
        $user_id = $request->user_id;
        $transactionId = $request->transactionId;
        $transactionContent = $transactionContent->where('user_id', $user_id)->where('id', $transactionId)->first();
        if($transactionContent){
            try {
                DB::beginTransaction();
                $transactionContent->delete();
                DB::commit();
    
                return response()->json(['message' => '家計簿を登録しました', 'id' => $transactionContent->id], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }
    }

    /**
     * 月次家計簿データを取得
     *
     * 指定された月のトランザクションデータを取得します。
     *
     * @param Request $request リクエストデータ（currentMonthを含む）。
     * @param Content $transactionContent 家計簿モデルのインスタンス。
     * @return \Illuminate\Http\JsonResponse 月次データをJSON形式で返します。
     */
    public function getMonthlyTransaction(Request $request, Content $transactionContent)
    {
        try {
            $monthlyTransactionData = $transactionContent->getMonthlyTransaction($request);
            $preMonthlyTransactionData = $transactionContent->getPreMonthlyTransaction($request);
            return response()->json(['message' => '選択月の家計簿を取得しました。', 'monthlyTransactionData' => $monthlyTransactionData, 'preMonthlyTransactionData' => $preMonthlyTransactionData], Response::HTTP_OK);
        }catch (\Exception $e) {
            return response()->json(['message' => '選択月の家計簿はありません'], Response::HTTP_NOT_FOUND);
        }
    }
    
    /**
     * 年次家計簿データを取得
     *
     * 指定された年のトランザクションデータを取得します。
     *
     * @param Request $request リクエストデータ（currentYearを含む）。
     * @param Content $transactionContent 家計簿モデルのインスタンス。
     * @return \Illuminate\Http\JsonResponse 年次データをJSON形式で返します。
     */
    public function getYearlyTransaction(Request $request, Content $transactionContent)
    {
        try {
            $yearlyTransactionData = $transactionContent->getYearlyTransaction($request);
            $preYearlyTransactionData = $transactionContent->getPreYearlyTransaction($request);
            return response()->json(['message' => '選択年の家計簿を取得しました。', 'yearlyTransactionData' => $yearlyTransactionData, 'preYearlyTransactionData' => $preYearlyTransactionData], Response::HTTP_OK);
        }catch (\Exception $e) {
            return response()->json(['message' => '選択年の家計簿はありません'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * 複数の家計簿をコピー
     *
     * 指定された複数の支出を別の日付にコピーします。
     *
     * @param CopyMultipleContentsRequest $request リクエストデータ（source_date, destination_date, content_ids）。
     * @return \Illuminate\Http\JsonResponse コピー結果をJSON形式で返します。
     */
    public function copyMultipleContents(CopyMultipleContentsRequest $request)
    {
        $validated = $request->validated();

        // Validation: source_date !== destination_date
        if ($validated['source_date'] === $validated['destination_date']) {
            return response()->json([
                'success' => false,
                'message' => 'コピー先の日付は異なる日付を選択してください',
                'error_code' => 'SAME_DATE_ERROR',
            ], 400);
        }

        try {
            // Fetch source contents
            $sourceContents = Content::where('user_id', auth()->id())
                ->where('recorded_at', $validated['source_date'])
                ->whereIn('id', $validated['content_ids'])
                ->get();

            if ($sourceContents->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'コピー対象の支出が見つかりません',
                ], 404);
            }

            // Transaction: copy all contents
            $createdIds = [];
            DB::beginTransaction();

            foreach ($sourceContents as $content) {
                $newContent = $content->replicate();
                $newContent->recorded_at = $validated['destination_date'];
                $newContent->save();
                $createdIds[] = $newContent->id;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($createdIds) . '件の支出をコピーしました',
                'copied_count' => count($createdIds),
                'created_ids' => $createdIds,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'コピー処理中にエラーが発生しました',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 複数月分のトランザクションを取得（3ヶ月版）
     *
     * 指定された月を中心に、前月・当月・次月の3ヶ月分のデータを取得します。
     * GET /api/monthly-transactions-multi?month=202604&months=3
     *
     * @param Request $request クエリパラメータ（month: YYYYMMの形式、months: 月数（デフォルト3））
     * @return \Illuminate\Http\JsonResponse 3ヶ月分のトランザクションをJSON形式で返します。
     */
    public function getMonthlyTransactions3months(Request $request)
    {
        try {
            $userId = auth()->id();
            $month = $request->query('month'); // 'YYYYMm' 形式
            $months = (int)$request->query('months', 3); // デフォルト3ヶ月

            if (!$month || !preg_match('/^\d{6}$/', $month)) {
                return response()->json([
                    'success' => false,
                    'message' => 'month parameter is required in YYYYMM format',
                ], 400);
            }

            // 月文字列をCarbon日付に変換
            $currentDate = Carbon::createFromFormat('YmdHis', $month . '010000')->startOfMonth();

            // 期間を計算
            $startDate = $currentDate->copy()->subMonths($months - 1)->startOfMonth();
            $endDate = $currentDate->copy()->addMonths($months - 1)->endOfMonth();

            // クエリ実行（複合インデックス活用）
            $allContents = Content::where('user_id', $userId)
                ->whereBetween('recorded_at', [$startDate, $endDate])
                ->orderBy('recorded_at', 'asc')
                ->select('id', 'user_id', 'type_id', 'category_id', 'amount', 'content', 'recorded_at', 'created_at')
                ->get();

            // 月ごとにグループ化
            $prevMonthStart = $currentDate->copy()->subMonth()->startOfMonth();
            $prevMonthEnd = $prevMonthStart->copy()->endOfMonth();

            $currentMonthStart = $currentDate->copy()->startOfMonth();
            $currentMonthEnd = $currentDate->copy()->endOfMonth();

            $nextMonthStart = $currentDate->copy()->addMonth()->startOfMonth();
            $nextMonthEnd = $nextMonthStart->copy()->endOfMonth();

            $prevMonth = $allContents->filter(function($content) use ($prevMonthStart, $prevMonthEnd) {
                $date = Carbon::parse($content->recorded_at);
                return $date->between($prevMonthStart, $prevMonthEnd);
            })->values();

            $currentMonthData = $allContents->filter(function($content) use ($currentMonthStart, $currentMonthEnd) {
                $date = Carbon::parse($content->recorded_at);
                return $date->between($currentMonthStart, $currentMonthEnd);
            })->values();

            $nextMonth = $allContents->filter(function($content) use ($nextMonthStart, $nextMonthEnd) {
                $date = Carbon::parse($content->recorded_at);
                return $date->between($nextMonthStart, $nextMonthEnd);
            })->values();

            return response()->json([
                'success' => true,
                'prevMonth' => $prevMonth,
                'currentMonth' => $currentMonthData,
                'nextMonth' => $nextMonth,
                'month' => $month,
            ], 200)->header('Cache-Control', 'private, max-age=3600');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch monthly transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}