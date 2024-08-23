<?php

namespace App\Http\Controllers;

use App\Models\ExpenceCategory;
use App\Models\FixedCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    //
    public function index(Request $request, ExpenceCategory $expenseCategory){
        $fixedExpenseCategory = FixedCategory::where('type_id', 2)->get()->toArray();
        $expenseUserCategory = $expenseCategory->where('user_id', $request->user_id)->where('deleted', 0)->get()->toArray();
        $expenseUserCategory = count($expenseUserCategory) !== 0 ? array_merge($fixedExpenseCategory, $expenseUserCategory) : $fixedExpenseCategory;
        return response()->json(['status' => 200, "expenseUserCategory"=>$expenseUserCategory]); 
    }
}