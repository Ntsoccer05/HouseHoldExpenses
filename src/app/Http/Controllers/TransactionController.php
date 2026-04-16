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
     * 複数月のトランザクションデータを一括取得
     *
     * 基準月を中心に前々月・前月・当月・翌月の4ヶ月分を返します。
     * レスポンス形式: { "202602": [...], "202603": [...], "202604": [...], "202605": [...] }
     *
     * @param Request $request base_month（Ym形式）, user_id を含むリクエスト
     * @param Content $transactionContent 家計簿モデルのインスタンス
     * @return \Illuminate\Http\JsonResponse 月ごとのデータをJSON形式で返します
     */
    public function getMonthlyTransactionsBulk(Request $request, Content $transactionContent)
    {
        try {
            $baseMonth = $request->base_month;
            $userId = $request->user_id;

            $baseDate = Carbon::createFromFormat('Ym', $baseMonth);

            $result = [];
            // 前々月(-2), 前月(-1), 当月(0), 翌月(+1) の4ヶ月分
            for ($i = -2; $i <= 1; $i++) {
                $targetMonth = $baseDate->copy()->addMonths($i)->format('Ym');
                $result[$targetMonth] = $transactionContent->getMonthlyTransactionForMonth($userId, $targetMonth);
            }

            return response()->json($result, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['message' => 'データの取得に失敗しました'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
}