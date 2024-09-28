<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Models\Content;
use App\Models\ExpenceCategory;
use App\Models\IncomeCategory;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use DateTime;

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
    public function create(TransactionRequest $request, Content $transactionContent, Type $type){
        $user_id = $request->user_id;
        $contents = $request->transaction;
        try {
            // Start a new database transaction
            DB::beginTransaction();
            $transactionContent->user_id = $user_id;
            $transactionContent->type_id = $type->where('en_name', $contents['type'])->value('id');
            $transactionContent->insertCategory($contents, $transactionContent->type_id);
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
        Log::error($transactionContent);
        if($transactionContent){
            try {
                // Start a new database transaction
                DB::beginTransaction();
                $transactionContent->type_id = $type->where('en_name', $contents['type'])->value('id');
                $transactionContent->insertCategory($contents, $transactionContent->type_id);
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
    public function delete(Request $request, Content $transactionContent){
        $user_id = $request->user_id;
        $transactionId = $request->transactionId;
        $transactionContent = $transactionContent->where('user_id', $user_id)->where('id', $transactionId)->first();
        Log::error($transactionContent);
        if($transactionContent){
            try {
                // Start a new database transaction
                DB::beginTransaction();
                $transactionContent->delete();
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
}