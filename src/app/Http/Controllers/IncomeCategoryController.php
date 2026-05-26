<?php

namespace App\Http\Controllers;

use App\Models\IncomeCategory;
use Illuminate\Http\Request;

const INCOME_TYPE = 1;

class IncomeCategoryController extends Controller
{
    /**
     * ユーザーの収入カテゴリを取得し、固定カテゴリとマージして返すメソッド。
     * @param \Illuminate\Http\Request $request リクエストオブジェクト
     * @param \App\Models\IncomeCategory $incomeCategory 収入カテゴリモデル
     * @return \Illuminate\Http\JsonResponse ユーザーの収入カテゴリのリストを含むJSONレスポンス
     */
    public function index(Request $request, IncomeCategory $incomeCategory){
        $incomeUserCategory = $incomeCategory->where('user_id', $request->user()->id)->orderBy('filtered_id')->get()->toArray();

        return response()->json(['status' => 200, "incomeUserCategory"=>$incomeUserCategory]); 
    }

    /**
     * 新しい収入カテゴリを作成
     *
     * @param \Illuminate\Http\Request $request HTTPリクエストオブジェクト。リクエストには、ユーザーIDとカテゴリデータが含まれています。
     * @param \App\Models\IncomeCategory $incomeCategory 収入カテゴリモデルインスタンス。
     * @return \Illuminate\Http\JsonResponse カテゴリ作成の結果をJSON形式で返します。
     */
    public function add(Request $request, IncomeCategory $incomeCategory){
        $user_id = $request->user_id;
        // 最後の filtered_id を取得（なければ null）
        $last = $incomeCategory->where('user_id', $user_id)
            ->where('deleted', 0)
            ->orderBy('filtered_id', 'DESC')
            ->first('filtered_id');

        // null チェックして初期値を決める
        $newFilteredId = $last ? $last->filtered_id + 1 : 1;
        $createData = $request->data;
        $incomeCategory->type_id = config('app.expense_type_id');
        $incomeCategory->user_id = $user_id;
        $incomeCategory->filtered_id = $newFilteredId;
        $incomeCategory->icon = isset($createData['icon']) ? $createData['icon'] : "";
        $incomeCategory->content = $createData['content'];
        $incomeCategory->save();
        
        return response()->json(['status' => 200, "message"=>"カテゴリを作成しました"]); 
    }

    /**
     * 収入カテゴリを更新
     *
     * @param \Illuminate\Http\Request $request HTTPリクエストオブジェクト。リクエストにはユーザーIDと更新データが含まれています。
     * @param \App\Models\IncomeCategory $incomeCategory 収入カテゴリモデルインスタンス。
     * @return \Illuminate\Http\JsonResponse カテゴリ更新の結果をJSON形式で返します。
    */
    public function update(Request $request, IncomeCategory $incomeCategory){
        $user_id = $request->user_id;
        $updateData = $request->updateData;
        $updateData['user_id'] = $user_id;
        $incomeUserCategory = $incomeCategory->where('user_id', $user_id)->where('id', $updateData['id'])->where('deleted', 0)->first();
        $incomeCategory->createOrUpdateData($incomeUserCategory, $updateData);
        return response()->json(['status' => 200, "message"=>"カテゴリを更新しました"]); 
    }

    /**
     * 並び替え・ラベル・アイコンをトランザクションで一括更新
     */
    public function batchSave(Request $request, IncomeCategory $incomeCategory){
        try {
            $incomeCategory->batchUpdateData($request->user_id, $request->categories);
            return response()->json(['status' => 200, "message" => "カテゴリを更新しました"]);
        } catch(\Exception $e) {
            return response()->json(['status' => 500, "message" => "カテゴリ更新に失敗しました"], 500);
        }
    }

    /**
     * 収入カテゴリを並び替え
     *
     * @param \Illuminate\Http\Request $request リクエストオブジェクト（並び替え対象のカテゴリリストを含む）
     * @param \App\Models\IncomeCategory $incomeCategory 収支カテゴリモデル
     * @return \Illuminate\Http\JsonResponse カテゴリ削除処理の結果を返すJSONレスポンス
    */
    public function sort(Request $request, IncomeCategory $incomeCategory){
        $user_id = $request->user_id;
        $sortData = json_encode($request->sortData['tgtCategories']);
        $sortData = json_decode($sortData);
        foreach($sortData as $tgtData){
            $tgtData->user_id = $user_id;
            $first_id = $incomeCategory->where('user_id', $user_id)->where('deleted', 0)->first('id');
            $incomeUserCategory = $incomeCategory->where('user_id', $user_id)->where('content', $tgtData->label)->where('deleted', 0)->first();
            $incomeCategory->sortData($incomeUserCategory, $tgtData, $first_id->id);
        }
        return response()->json(['status' => 200, "message"=>"カテゴリを並び替えしました"]); 
    }

    /**
     * 収入カテゴリを削除
     *
     * @param \Illuminate\Http\Request $request HTTPリクエストオブジェクト。リクエストにはユーザーIDと更新データが含まれています。
     * @param \App\Models\IncomeCategory $incomeCategory 収入カテゴリモデルインスタンス。
     * @return \Illuminate\Http\JsonResponse カテゴリ更新の結果をJSON形式で返します。
    */
    public function delete(Request $request, IncomeCategory $incomeCategory){
        try{
            $deleteData = json_decode(json_encode($request->deleteData['tgtCategories']));
            $incomeCategory->batchDeleteData($request->user_id, $deleteData);
            return response()->json(['status' => 200, "message"=>"カテゴリを削除しました"]);
        }catch(\Exception $e){
            return response()->json(['status' => 500, "message"=>"カテゴリ削除失敗しました"]);
        }
    }
}