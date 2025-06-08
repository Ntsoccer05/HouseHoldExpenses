<?php

namespace App\Http\Controllers;

use App\Enums\TypeEnum;
use App\Http\Requests\TransactionRequest;
use App\Models\Content;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;
use Illuminate\Http\Response;

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
            return response()->json(['message' => '選択月の家計簿を取得しました。', 'monthlyTransactionData' => $monthlyTransactionData], Response::HTTP_OK);
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
            return response()->json(['message' => '選択年の家計簿を取得しました。', 'yearlyTransactionData' => $yearlyTransactionData], Response::HTTP_OK);
        }catch (\Exception $e) {
            return response()->json(['message' => '選択年の家計簿はありません'], Response::HTTP_NOT_FOUND);
        }
    }
}