<?php

namespace App\Http\Controllers;

use App\Models\ExpenceCategory;
use Illuminate\Http\Request;

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
        // $fixedExpenseCategory = FixedCategory::where('type_id', EXPENCE_TYPE)->get()->map(function ($item) {
        //     $item['fixed_category_id'] = $item['id']; // idと同じ値のfixed_category_idを追加
        //     return $item;
        // })->toArray();
        // $expenseUserCategory = $expenseCategory->where('user_id', $request->user_id)->get()->toArray();
        // if(count($expenseUserCategory) !== 0){
        //     // $fixedExpenseCategoryをコレクションに変換し、idをキーに設定
        //     $fixedExpenseCategoryCollection = collect($fixedExpenseCategory)->keyBy('id');
        //     // $expenseUserCategoryをコレクションに変換し、fixed_category_idをキーに設定
        //     $expenseUserCategoryCollection = collect($expenseUserCategory);

        //     // 固定カテゴリとユーザーの収支カテゴリをマージ
        //     $combinedCategories = $fixedExpenseCategoryCollection->map(function ($fixedCategory) use ($expenseUserCategoryCollection) {
        //         // 固定カテゴリが存在する場合は、ユーザーのデータで上書き
        //         return $expenseUserCategoryCollection->firstWhere('fixed_category_id', $fixedCategory['id']) ?: $fixedCategory;
        //     })->values();
        //     // fixed_category_idがnullの$expenseUserCategoryのデータを追加
        //     $nullFixedCategory = $expenseUserCategoryCollection->filter(function ($category) {
        //         return is_null($category['fixed_category_id']);
        //     });
        //     // 結合し、インデックスをリセット
        //     $mergedCategories = $combinedCategories->merge($nullFixedCategory)->values();
        //     // idを上から順に再設定 array_values() はPHPの配列に対してキーを連番にリセット
        //     $expenseUserCategory = array_values($mergedCategories->filter(function ($category) {
        //             // 'deleted' キーが存在し、かつ 'deleted' が 1 でないものを残す
        //             return !isset($category['deleted']) || $category['deleted'] !== 1;
        //         })->map(function ($category, $index) {
        //             $category['filtered_id'] = $index + 1; // idを1から順に再設定
        //             return $category;
        //         })->toArray());
        // }else{
        //     $expenseUserCategory = $fixedExpenseCategory;
        //     foreach($expenseUserCategory as $index => $category){
        //         $expenseUserCategory[$index]['filtered_id'] = $index + 1;
        //     }
        // }
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
        $last_filter_id = $expenseCategory->where('user_id', $user_id)->where('deleted', 0)->orderBy('filtered_id', 'DESC')->first('filtered_id');
        $createData = $request->data;
        $expenseCategory->type_id = config('app.expense_type_id');
        $expenseCategory->user_id = $user_id;
        $expenseCategory->filtered_id = $last_filter_id->filtered_id + 1;
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
        // pluck()メソッドより、fixed_category_idの値だけが配列として返される
        // $fixedCategoryIds = $expenseCategory->where('id', $updateData['id'])->where('user_id', $user_id)->pluck('fixed_category_id');
        // $fixedCategoryIdsと一致しないidを持つデータを取得するためにwhereNotInメソッドを使用。
        // $fixedExpenseCategory = FixedCategory::whereNotIn('id', $fixedCategoryIds)->where('id', $updateData['id'])->first();
        // if($updateData['fixed_category_id']){
        //     $tgtExpenseCategory = $expenseCategory->where('user_id', $user_id)->where('fixed_category_id', $updateData['fixed_category_id'])->where('deleted', 0)->first();
        //     $expenseCategory->createOrUpdateData($tgtExpenseCategory, $updateData);
        // }else{
        $expenseUserCategory = $expenseCategory->where('user_id', $user_id)->where('id', $updateData['id'])->where('deleted', 0)->first();
        $expenseCategory->createOrUpdateData($expenseUserCategory, $updateData);
        // }
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
        $user_id = $request->user_id;
        $deleteData = json_encode($request->deleteData['tgtCategories']);
        $deleteData = json_decode($deleteData);
        foreach($deleteData as $tgtData){
            $tgtData->user_id = $user_id;
            // if(isset($tgtData->fixed_category_id)){
            //     $tgtExpenseCategory = $expenseCategory->where('user_id', $user_id)->where('fixed_category_id', $tgtData->fixed_category_id)->where('deleted', 0)->first();
            //     $expenseCategory->deleteData($tgtExpenseCategory, $tgtData);
            // }else{
                $expenseUserCategory = $expenseCategory->where('user_id', $user_id)->where('id', $tgtData->id)->where('deleted', 0)->first();
                $expenseCategory->deleteData($expenseUserCategory, $tgtData);
            // }
        }
        return response()->json(['status' => 200, "message"=>"カテゴリを削除しました"]); 
    }
}