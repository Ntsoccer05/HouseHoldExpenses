<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\ExpenceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

const EXPENCE_TYPE = 2;

class ExpenseCategoryController extends Controller
{
    /**
     * ユーザーの支出カテゴリを取得し、固定カテゴリとマージして返すメソッド。
     * @param \Illuminate\Http\Request $request リクエストオブジェクト
     * @param \App\Models\ExpenceCategory $expenseCategory 収入カテゴリモデル
     * @return \Illuminate\Http\JsonResponse ユーザーの収入カテゴリのリストを含むJSONレスポンス
     */
    public function index(Request $request, ExpenceCategory $expenseCategory){
        $expenseUserCategory = $expenseCategory->where('user_id', $request->user_id)->orderBy('filtered_id')->get()->toArray();
        
        return response()->json(['status' => 200, "expenseUserCategory"=>$expenseUserCategory]); 
    }

    /**
     * 新しい支出カテゴリを作成
     *
     * @param \Illuminate\Http\Request $request HTTPリクエストオブジェクト。リクエストには、ユーザーIDとカテゴリデータが含まれています。
     * @param \App\Models\ExpenceCategory $expenseCategory 収入カテゴリモデルインスタンス。
     * @return \Illuminate\Http\JsonResponse カテゴリ作成の結果をJSON形式で返します。
     */
    public function add(Request $request, ExpenceCategory $expenseCategory){
        $user_id = $request->user_id;
        $last = $expenseCategory->where('user_id', $user_id)
            ->where('deleted', 0)
            ->orderBy('filtered_id', 'DESC')
            ->first('filtered_id');

        // null チェックして初期値を決める
        $newFilteredId = $last ? $last->filtered_id + 1 : 1;
        $createData = $request->data;
        $expenseCategory->type_id = config('app.expense_type_id');
        $expenseCategory->user_id = $user_id;
        $expenseCategory->filtered_id = $newFilteredId;
        $expenseCategory->icon = isset($createData['icon']) ? $createData['icon'] : "";
        $expenseCategory->content = $createData['content'];
        $expenseCategory->save();
        
        return response()->json(['status' => 200, "message"=>"カテゴリを作成しました"]); 
    }

    /**
     * 支出カテゴリを更新
     *
     * @param \Illuminate\Http\Request $request HTTPリクエストオブジェクト。リクエストにはユーザーIDと更新データが含まれています。
     * @param \App\Models\ExpenceCategory $expenseCategory 収入カテゴリモデルインスタンス。
     * @return \Illuminate\Http\JsonResponse カテゴリ更新の結果をJSON形式で返します。
    */
    public function update(Request $request, ExpenceCategory $expenseCategory){
        $user_id = $request->user_id;
        $updateData = $request->updateData;
        $updateData['user_id'] = $user_id;
        $expenseUserCategory = $expenseCategory->where('user_id', $user_id)->where('id', $updateData['id'])->where('deleted', 0)->first();
        $expenseCategory->createOrUpdateData($expenseUserCategory, $updateData);
        return response()->json(['status' => 200, "message"=>"カテゴリを更新しました"]); 
    }

    /**
     * 支出カテゴリを並び替え
     *
     * @param \Illuminate\Http\Request $request リクエストオブジェクト（並び替え対象のカテゴリリストを含む）
     * @param \App\Models\ExpenceCategory $expenseCategory 収支カテゴリモデル
     * @return \Illuminate\Http\JsonResponse カテゴリ削除処理の結果を返すJSONレスポンス
    */
    public function sort(Request $request, ExpenceCategory $expenseCategory){
        $user_id = $request->user_id;
        $sortData = json_encode($request->sortData['tgtCategories']);
        $sortData = json_decode($sortData);
        foreach($sortData as $tgtData){
            $tgtData->user_id = $user_id;
            $first_id = $expenseCategory->where('user_id', $user_id)->where('deleted', 0)->first('id');
            $expenseUserCategory = $expenseCategory->where('user_id', $user_id)->where('content', $tgtData->label)->where('deleted', 0)->first();
            $expenseCategory->sortData($expenseUserCategory, $tgtData, $first_id->id);
        }
        return response()->json(['status' => 200, "message"=>"カテゴリを並び替えしました"]); 
    }

    /**
     * 支出カテゴリを削除
     *
     * @param \Illuminate\Http\Request $request リクエストオブジェクト（削除対象のカテゴリリストを含む）
     * @param \App\Models\ExpenceCategory $expenseCategory 収支カテゴリモデル
     * @return \Illuminate\Http\JsonResponse カテゴリ削除処理の結果を返すJSONレスポンス
    */
    public function delete(Request $request, ExpenceCategory $expenseCategory){
        try{
            DB::beginTransaction();
            $user_id = $request->user_id;
            $deleteData = json_encode($request->deleteData['tgtCategories']);
            $deleteData = json_decode($deleteData);
            foreach($deleteData as $tgtData){
                $tgtData->user_id = $user_id;
                $expenseUserCategory = $expenseCategory->where('user_id', $user_id)->where('id', $tgtData->id)->where('deleted', 0)->first();
                $expenseCategory->deleteData($expenseUserCategory, data: $tgtData);
                if($expenseUserCategory){
                    Content::deleteContentsByCategory($expenseUserCategory);
                }
            }
            DB::commit();
            return response()->json(['status' => 200, "message"=>"カテゴリを削除しました"]); 
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 500, "message"=>"カテゴリ削除失敗しました"]); 
        }
    }
}