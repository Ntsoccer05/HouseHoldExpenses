<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFixedExpenseRequest;
use App\Http\Requests\UpdateFixedExpenseRequest;
use App\Models\FixedExpense;
use Illuminate\Http\Request;

class FixedExpenseController extends Controller
{
    public function index(Request $request)
    {
        $fixedExpenses = FixedExpense::where('user_id', $request->user_id)
            ->orderBy('id')
            ->get();
        return response()->json(['status' => 200, 'fixedExpenses' => $fixedExpenses]);
    }

    public function store(StoreFixedExpenseRequest $request)
    {
        $fixedExpense = new FixedExpense();
        $fixedExpense->user_id           = $request->user_id;
        $fixedExpense->type_id           = config('app.expense_type_id');
        $fixedExpense->category_id       = $request->category_id;
        $fixedExpense->amount            = $request->amount;
        $fixedExpense->content           = $request->content;
        $fixedExpense->fixed_expense_day = $request->fixed_expense_day;
        $fixedExpense->save();
        return response()->json(['status' => 200, 'message' => '固定費を作成しました', 'fixedExpense' => $fixedExpense]);
    }

    public function update(UpdateFixedExpenseRequest $request, FixedExpense $fixedExpense)
    {
        if ($fixedExpense->user_id !== $request->user_id) {
            return response()->json(['status' => 403, 'message' => '権限がありません'], 403);
        }
        $fixedExpense->fill($request->only(['category_id', 'amount', 'content', 'fixed_expense_day', 'is_active']));
        $fixedExpense->save();
        return response()->json(['status' => 200, 'message' => '固定費を更新しました', 'fixedExpense' => $fixedExpense]);
    }

    public function destroy(Request $request, FixedExpense $fixedExpense)
    {
        if ($fixedExpense->user_id !== (int) $request->user_id) {
            return response()->json(['status' => 403, 'message' => '権限がありません'], 403);
        }
        $fixedExpense->delete();
        return response()->json(['status' => 200, 'message' => '固定費を削除しました']);
    }
}
