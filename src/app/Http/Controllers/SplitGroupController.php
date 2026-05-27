<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSplitGroupRequest;
use App\Http\Requests\UpdateSplitGroupRequest;
use App\Http\Requests\UpdateSplitGroupSettingsRequest;
use App\Http\Requests\UpdateCategoryOverridesRequest;
use App\Models\Content;
use App\Models\SplitGroup;
use Illuminate\Http\Request;

class SplitGroupController extends Controller
{
    public function index(Request $request)
    {
        $groups = SplitGroup::where('user_id', $request->user()->id)
            ->with(['setting', 'categoryOverrides'])
            ->orderBy('id')
            ->get()
            ->map(fn($g) => [
                'id'      => $g->id,
                'label'   => $g->label,
                'setting' => $g->setting ? [
                    'income_other_ratio'   => $g->setting->income_other_ratio,
                    'income_other_offset'  => $g->setting->income_other_offset,
                    'expense_other_ratio'  => $g->setting->expense_other_ratio,
                    'expense_other_offset' => $g->setting->expense_other_offset,
                ] : null,
                'category_overrides' => $g->categoryOverrides->map(fn($o) => [
                    'category_id' => $o->category_id,
                    'type_id'     => $o->type_id,
                    'other_ratio' => $o->other_ratio,
                ]),
            ]);

        return response()->json(['status' => 200, 'splitGroups' => $groups]);
    }

    public function store(StoreSplitGroupRequest $request)
    {
        $group = SplitGroup::create([
            'user_id' => $request->user()->id,
            'label'   => $request->label,
        ]);

        // settings レコードを自動作成（ratioはNULL）
        $group->setting()->create([]);
        $group->load(['setting', 'categoryOverrides']);

        return response()->json(['status' => 200, 'splitGroup' => $group], 201);
    }

    public function update(UpdateSplitGroupRequest $request, SplitGroup $splitGroup)
    {
        if ($splitGroup->user_id !== $request->user()->id) {
            return response()->json(['status' => 403, 'message' => '権限がありません'], 403);
        }

        $splitGroup->fill($request->only(['label']));
        $splitGroup->save();

        return response()->json(['status' => 200, 'splitGroup' => $splitGroup]);
    }

    public function destroy(Request $request, SplitGroup $splitGroup)
    {
        if ($splitGroup->user_id !== $request->user()->id) {
            return response()->json(['status' => 403, 'message' => '権限がありません'], 403);
        }

        $splitGroup->delete();

        return response()->json(['status' => 200, 'message' => '分担グループを削除しました']);
    }

    public function updateSettings(UpdateSplitGroupSettingsRequest $request, SplitGroup $splitGroup)
    {
        if ($splitGroup->user_id !== $request->user()->id) {
            return response()->json(['status' => 403, 'message' => '権限がありません'], 403);
        }

        $splitGroup->setting()->updateOrCreate(
            ['split_group_id' => $splitGroup->id],
            $request->only(['income_other_ratio', 'income_other_offset', 'expense_other_ratio', 'expense_other_offset'])
        );

        // overrides が含まれていれば一括置換
        if ($request->has('overrides')) {
            $splitGroup->categoryOverrides()->delete();
            foreach ($request->overrides ?? [] as $override) {
                $splitGroup->categoryOverrides()->create($override);
            }
        }

        $splitGroup->load(['setting', 'categoryOverrides']);

        return response()->json(['status' => 200, 'splitGroup' => $splitGroup]);
    }

    public function getCategoryOverrides(Request $request, SplitGroup $splitGroup)
    {
        if ($splitGroup->user_id !== $request->user()->id) {
            return response()->json(['status' => 403, 'message' => '権限がありません'], 403);
        }

        return response()->json(['status' => 200, 'categoryOverrides' => $splitGroup->categoryOverrides]);
    }

    public function updateCategoryOverrides(UpdateCategoryOverridesRequest $request, SplitGroup $splitGroup)
    {
        if ($splitGroup->user_id !== $request->user()->id) {
            return response()->json(['status' => 403, 'message' => '権限がありません'], 403);
        }

        // 既存を全削除してから再登録（一括置換）
        $splitGroup->categoryOverrides()->delete();
        foreach ($request->overrides as $override) {
            $splitGroup->categoryOverrides()->create($override);
        }
        $splitGroup->load('categoryOverrides');

        return response()->json(['status' => 200, 'categoryOverrides' => $splitGroup->categoryOverrides]);
    }

    public function monthlySummary(Request $request)
    {
        $request->validate(['month' => 'required|string|regex:/^\d{6}$/']);

        $month    = $request->month;
        $year     = (int) substr($month, 0, 4);
        $monthNum = (int) substr($month, 4, 2);

        $contents = Content::where('user_id', $request->user()->id)
            ->whereYear('recorded_at', $year)
            ->whereMonth('recorded_at', $monthNum)
            ->get();

        $income  = $contents->where('type_id', config('app.income_type_id'))->sum('amount');
        $expense = $contents->where('type_id', config('app.expense_type_id'))->sum('amount');

        return response()->json([
            'status'  => 200,
            'income'  => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ]);
    }

    public function preview(Request $request, SplitGroup $splitGroup)
    {
        $request->validate(['month' => 'required|string|regex:/^\d{6}$/']);

        if ($splitGroup->user_id !== $request->user()->id) {
            return response()->json(['status' => 403, 'message' => '権限がありません'], 403);
        }

        $month    = $request->month;
        $year     = (int) substr($month, 0, 4);
        $monthNum = (int) substr($month, 4, 2);

        $splitGroup->load(['setting', 'categoryOverrides']);
        $setting = $splitGroup->setting;

        $base = [
            'status'      => 200,
            'group_label' => $splitGroup->label,
            'month'       => sprintf('%d-%02d', $year, $monthNum),
        ];

        if (!$setting) {
            return response()->json($base);
        }

        // 対象月のトランザクション取得
        $contents = Content::where('user_id', $request->user()->id)
            ->whereYear('recorded_at', $year)
            ->whereMonth('recorded_at', $monthNum)
            ->get();

        // カテゴリ上書きを "{type_id}_{category_id}" => ratio でインデックス化
        $overrideMap = [];
        foreach ($splitGroup->categoryOverrides as $override) {
            $overrideMap["{$override->type_id}_{$override->category_id}"] = $override->other_ratio;
        }

        $incomeTypeId    = config('app.income_type_id');
        $expenseTypeId   = config('app.expense_type_id');
        $incomeContents  = $contents->where('type_id', $incomeTypeId);
        $expenseContents = $contents->where('type_id', $expenseTypeId);
        $incomeTotal     = $incomeContents->sum('amount');
        $expenseTotal    = $expenseContents->sum('amount');

        $result = $base;

        // 収入の按分（ratio が NULL なら出力しない）
        if ($setting->income_other_ratio !== null) {
            $incomeOther = 0;
            foreach ($incomeContents->groupBy('category_id') as $categoryId => $items) {
                $ratio        = $overrideMap["{$incomeTypeId}_{$categoryId}"] ?? $setting->income_other_ratio;
                $incomeOther += (int) floor($items->sum('amount') * $ratio / 100);
            }
            $incomeOther += (int) ($setting->income_other_offset ?? 0);

            $result['income'] = [
                'total' => $incomeTotal,
                'self'  => $incomeTotal - $incomeOther,
                'other' => $incomeOther,
            ];
        }

        // 支出の按分（ratio が NULL なら出力しない）
        if ($setting->expense_other_ratio !== null) {
            $expenseOther = 0;
            foreach ($expenseContents->groupBy('category_id') as $categoryId => $items) {
                $ratio         = $overrideMap["{$expenseTypeId}_{$categoryId}"] ?? $setting->expense_other_ratio;
                $expenseOther += (int) floor($items->sum('amount') * $ratio / 100);
            }
            $expenseOther += (int) ($setting->expense_other_offset ?? 0);

            $result['expense'] = [
                'total' => $expenseTotal,
                'self'  => $expenseTotal - $expenseOther,
                'other' => $expenseOther,
            ];
        }

        // 残高：total は常に出力。self/other は両方設定済み時のみ
        $result['balance'] = ['total' => $incomeTotal - $expenseTotal];
        if (isset($result['income']) && isset($result['expense'])) {
            $result['balance']['self']  = $result['income']['self'] - $result['expense']['self'];
            $result['balance']['other'] = $result['income']['other'] - $result['expense']['other'];
        }

        return response()->json($result);
    }
}
