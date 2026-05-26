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
            ->get();

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

        $splitGroup->fill($request->only(['label', 'is_active']));
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

        $result = $base;

        // 収入の按分（NULL なら出力しない）
        if ($setting->income_other_ratio !== null) {
            $incomeTypeId   = config('app.income_type_id');
            $incomeContents = $contents->where('type_id', $incomeTypeId);
            $incomeTotal    = $incomeContents->sum('amount');
            $incomeOther    = 0;

            foreach ($incomeContents->groupBy('category_id') as $categoryId => $items) {
                $categoryTotal = $items->sum('amount');
                $ratio         = $overrideMap["{$incomeTypeId}_{$categoryId}"] ?? $setting->income_other_ratio;
                $incomeOther  += (int) floor($categoryTotal * $ratio / 100);
            }

            $incomeOther += (int) ($setting->income_other_offset ?? 0);

            $result['income'] = [
                'total'       => $incomeTotal,
                'self'        => $incomeTotal - $incomeOther,
                'other'       => $incomeOther,
                'self_ratio'  => 100 - $setting->income_other_ratio,
                'other_ratio' => $setting->income_other_ratio,
                'other_offset' => $setting->income_other_offset,
            ];
        }

        // 支出の按分（NULL なら出力しない）
        if ($setting->expense_other_ratio !== null) {
            $expenseTypeId   = config('app.expense_type_id');
            $expenseContents = $contents->where('type_id', $expenseTypeId);
            $expenseTotal    = $expenseContents->sum('amount');
            $expenseOther    = 0;

            foreach ($expenseContents->groupBy('category_id') as $categoryId => $items) {
                $categoryTotal  = $items->sum('amount');
                $ratio          = $overrideMap["{$expenseTypeId}_{$categoryId}"] ?? $setting->expense_other_ratio;
                $expenseOther  += (int) floor($categoryTotal * $ratio / 100);
            }

            $expenseOther += (int) ($setting->expense_other_offset ?? 0);

            $result['expense'] = [
                'total'       => $expenseTotal,
                'self'        => $expenseTotal - $expenseOther,
                'other'       => $expenseOther,
                'self_ratio'  => 100 - $setting->expense_other_ratio,
                'other_ratio' => $setting->expense_other_ratio,
                'other_offset' => $setting->expense_other_offset,
            ];
        }

        // 残高：収入・支出の両方が設定済みの場合のみ
        if (isset($result['income']) && isset($result['expense'])) {
            $result['balance'] = [
                'total' => $result['income']['total'] - $result['expense']['total'],
                'self'  => $result['income']['self'] - $result['expense']['self'],
                'other' => $result['income']['other'] - $result['expense']['other'],
            ];
        }

        return response()->json($result);
    }
}
