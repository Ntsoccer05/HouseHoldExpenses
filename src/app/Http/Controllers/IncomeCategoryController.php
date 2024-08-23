<?php

namespace App\Http\Controllers;

use App\Models\FixedCategory;
use App\Models\IncomeCategory;
use Illuminate\Http\Request;

class IncomeCategoryController extends Controller
{
    //
    public function index(Request $request, IncomeCategory $incomeCategory){
        $fixedIncomeCategory = FixedCategory::where('type_id', 1)->get()->toArray();
        $incomeUserCategory = $incomeCategory->where('user_id', $request->user_id)->where('deleted', 0)->get()->toArray();
        $incomeUserCategory = count($incomeUserCategory) !== 0 ? array_merge($fixedIncomeCategory, $incomeUserCategory) : $fixedIncomeCategory;
        return response()->json(['status' => 200, "incomeUserCategory"=>$incomeUserCategory]); 
    }
}