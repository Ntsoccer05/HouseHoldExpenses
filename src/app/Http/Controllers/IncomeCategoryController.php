<?php

namespace App\Http\Controllers;

use App\Models\FixedCategory;
use App\Models\IncomeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        // $fixedIncomeCategory = FixedCategory::where('type_id', INCOME_TYPE)->get()->map(function ($item) {
        //     $item['fixed_category_id'] = $item['id']; // idと同じ値のfixed_category_idを追加
        //     return $item;
        // })->toArray();
        // $incomeUserCategory = $incomeCategory->where('user_id', $request->user_id)->get()->toArray();
        // if(count($incomeUserCategory) !== 0){
        //     // $fixedIncomeCategoryをコレクションに変換し、idをキーに設定
        //     $fixedIncomeCategoryCollection = collect($fixedIncomeCategory)->keyBy('id');
        //     // $incomeUserCategoryをコレクションに変換し、fixed_category_idをキーに設定
        //     $incomeUserCategoryCollection = collect($incomeUserCategory);
        //     $combinedCategories = $fixedIncomeCategoryCollection->map(function ($fixedCategory) use ($incomeUserCategoryCollection) {
        //         // 固定カテゴリが存在する場合は、ユーザーのデータで上書き
        //         return $incomeUserCategoryCollection->firstWhere('fixed_category_id', $fixedCategory['id']) ?: $fixedCategory;
        //     })->values();
        //     // fixed_category_idがnullの$incomeUserCategoryのデータを追加
        //     $nullFixedCategory = $incomeUserCategoryCollection->filter(function ($category) {
        //         return is_null($category['fixed_category_id']);
        //     });
        //     // 結合し、インデックスをリセット
        //     $mergedCategories = $combinedCategories->merge($nullFixedCategory)->values();
        //     // idを上から順に再設定
        //     $incomeUserCategory = array_values($mergedCategories->filter(function ($category) {
        //             // 'deleted' キーが存在し、かつ 'deleted' が 1 でないものを残す
        //             return !isset($category['deleted']) || $category['deleted'] !== 1;
        //         })->map(function ($category, $index) {
        //             $category['filtered_id'] = $index + 1; // idを1から順に再設定
        //             return $category;
        //         })->toArray());
        // }else{
        //     $incomeUserCategory = $fixedIncomeCategory;
        //     foreach($incomeUserCategory as $index => $category){
        //         $incomeUserCategory[$index]['filtered_id'] = $index + 1;
        //     }
        // }
        $incomeUserCategory = $incomeCategory->where('user_id', $request->user_id)->orderBy('filtered_id')->get()->toArray();

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
        $last_filter_id = $incomeCategory->where('user_id', $user_id)->where('deleted', 0)->orderBy('filtered_id', 'DESC')->first('filtered_id');
        $createData = $request->data;
        $incomeCategory->type_id = config('app.expense_type_id');
        $incomeCategory->user_id = $user_id;
        $incomeCategory->filtered_id = $last_filter_id->filtered_id + 1;
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
        // if($updateData['fixed_category_id']){
        //     $tgtIncomeCategory = $incomeCategory->where('user_id', $user_id)->where('fixed_category_id', $updateData['fixed_category_id'])->where('deleted', 0)->first();
        //     $incomeCategory->createOrUpdateData($tgtIncomeCategory, $updateData);
        // }else{
            $incomeUserCategory = $incomeCategory->where('user_id', $user_id)->where('id', $updateData['id'])->where('deleted', 0)->first();
            $incomeCategory->createOrUpdateData($incomeUserCategory, $updateData);
        // }
        return response()->json(['status' => 200, "message"=>"カテゴリを更新しました"]); 
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
        $user_id = $request->user_id;
        $deleteData = json_encode($request->deleteData['tgtCategories']);
        $deleteData = json_decode($deleteData);
        foreach($deleteData as $tgtData){
            $tgtData->user_id = $user_id;
            // if(isset($tgtData->fixed_category_id)){
            //     $tgtIncomeCategory = $incomeCategory->where('user_id', $user_id)->where('fixed_category_id', $tgtData->fixed_category_id)->where('deleted', 0)->first();
            //     $incomeCategory->deleteData($tgtIncomeCategory, $tgtData);
            // }else{
                $incomeUserCategory = $incomeCategory->where('user_id', $user_id)->where('id', $tgtData->id)->where('deleted', 0)->first();
                $incomeCategory->deleteData($incomeUserCategory, $tgtData);
            // }
        }
        return response()->json(['status' => 200, "message"=>"カテゴリを削除しました"]); 
    }
}