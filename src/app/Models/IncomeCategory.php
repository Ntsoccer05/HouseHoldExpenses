<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class IncomeCategory extends Model
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


    // 複数のデータには使えない、一つ一つのデータに対してのみ
    // public function getIncomeCategory()
    // {
    //     $fixedIncomeCategory = FixedCategory::where('type_id', 1)->get();
    //     $incomeCategory = array_merge($this, $fixedIncomeCategory);
    //     return $incomeCategory;
    // }
}