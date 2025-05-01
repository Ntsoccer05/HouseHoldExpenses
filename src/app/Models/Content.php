<?php

namespace App\Models;

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

    /**
     * Userモデルとのリレーション（多対1）
     * 
     * @return BelongsTo
     */
    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Typeモデルとのリレーション（多対1）
     * 
     * @return BelongsTo
     */
    public function type():BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * カテゴリをデータベースに挿入
     * 
     * 与えられたデータとタイプIDに基づいて適切なカテゴリを設定します。
     * 
     * @param array $data カテゴリ情報を含むデータ
     * @param int $type_id タイプID（収入または支出を識別）
     * @return void
     */
    public function insertCategory($data, $type_id){
        if($type_id === config('app.expense_type_id')){
            $expenseCategory = new ExpenceCategory();
            $this->category_id = $expenseCategory->where('content', $data['category'])->value('id');
        }elseif($type_id === config('app.income_type_id')){
            $incomeCategory = new IncomeCategory();
            $this->category_id = $incomeCategory->where('content', $data['category'])->value('id');
        }
    }

    /**
     * トランザクションデータを整形
     * 
     * 指定されたコンテンツデータをフォーマットして返します。
     * 
     * @param Content $content トランザクションデータ
     * @return array 整形されたトランザクションデータ
     */
    public function formatedTransaction($content){
        $type_name = $content->type->en_name;
        if($type_name === 'expense'){
            $expenseCategory = new ExpenceCategory();
            $category_name = $expenseCategory->where('id', $content->category_id)->value('content');
            $category_icon = $expenseCategory->where('id', $content->category_id)->value('icon');
        }elseif($type_name === 'income'){
            $incomeCategory = new IncomeCategory();
            $category_name = $incomeCategory->where('id', $content->category_id)->value('content');
            $category_icon = $incomeCategory->where('id', $content->category_id)->value('icon');
        }
        $formattedRecordedDay = (new DateTime($content->recorded_at))->format('Y-m-d');
        $formatedTransaction = [
            'id'=> $content->id,
            'date'=> $formattedRecordedDay,
            'amount'=> $content->amount,
            'content'=> $content->content,
            'type'=> $type_name,
            'category'=> $category_name,
            'icon'=> $category_icon,
        ];
        return $formatedTransaction;
    }

     /**
     * 月次トランザクションデータを取得
     * 
     * 指定された月のトランザクションデータを整形して返します。
     * 
     * @param object $request リクエストデータ（currentMonthを含む）
     * @return array 整形された月次データ
     */
    public function getMonthlyTransaction($request){
        $monthlyData = [];
        $data = $this::with(['type'])->whereRaw('DATE_FORMAT(recorded_at, "%Y%m") = ?', [$request->currentMonth])->get();
        foreach($data as $index => $content){
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
     * @param object $request リクエストデータ（currentYearを含む）
     * @return array 整形された年次データ
     */
    public function getYearlyTransaction($request){
        $yearlyData = [];
        $data = $this::with(['type'])->whereRaw('DATE_FORMAT(recorded_at, "%Y") = ?', [$request->currentYear])->get();
        foreach($data as $index => $content){
            $formatedTransaction = $this->formatedTransaction($content);
            $yearlyData[] = $formatedTransaction;
        }
        return $yearlyData;
    }

    /**
     * カテゴリを削除
     * @param ExpenceCategory|IncomeCategory $userCategory
     */
    public static function deleteContentsByCategory($userCategory)
    {
        //static メソッド内では $this は使えない
        self::where('category_id', $userCategory->id)->where('type_id', $userCategory->type_id)->delete();
    }
}