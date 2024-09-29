<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Content extends Model
{
    use HasFactory;

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type():BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function insertCategory($data, $type_id){
        if($type_id === config('app.expense_type_id')){
            $expenseCategory = new ExpenceCategory();
            $this->category_id = $expenseCategory->where('content', $data['category'])->value('id');
        }elseif($type_id === config('app.income_type_id')){
            $incomeCategory = new IncomeCategory();
            $this->category_id = $incomeCategory->where('content', $data['category'])->value('id');
        }
    }

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
}