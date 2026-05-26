# 分担管理＆SNS共有機能 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** ユーザーが月の収支を「自分 / グループ（パートナー等）」に按分してテキスト共有できる機能を実装する。

**Architecture:** バックエンドに `split_groups` / `split_group_settings` / `split_group_category_overrides` の3テーブルを追加し、既存テーブルは変更しない。SplitGroupController が按分計算も担当する（薄いコントローラーパターンに合わせ Service 層は設けない）。フロントは FixedExpense と同じ Context + Page + Component 構成。

**Tech Stack:** Laravel 10 / PHP 8.2 / MySQL 8.0 / React 18 / TypeScript / MUI v5 / react-hook-form + zod

---

## ファイルマップ

### バックエンド（`c:\WorkSpace\HouseHoldExpenses\src\`）

| 操作 | ファイル |
|------|---------|
| Create | `database/migrations/*_create_split_groups_table.php` |
| Create | `database/migrations/*_create_split_group_settings_table.php` |
| Create | `database/migrations/*_create_split_group_category_overrides_table.php` |
| Create | `app/Models/SplitGroup.php` |
| Create | `app/Models/SplitGroupSetting.php` |
| Create | `app/Models/SplitGroupCategoryOverride.php` |
| Create | `app/Http/Requests/StoreSplitGroupRequest.php` |
| Create | `app/Http/Requests/UpdateSplitGroupRequest.php` |
| Create | `app/Http/Requests/UpdateSplitGroupSettingsRequest.php` |
| Create | `app/Http/Requests/UpdateCategoryOverridesRequest.php` |
| Create | `app/Http/Controllers/SplitGroupController.php` |
| Modify | `routes/api.php` |
| Create | `tests/Feature/SplitGroupTest.php` |

### フロントエンド（`C:\WorkSpace\FrontendHouseHoldExpenses\src\`）

| 操作 | ファイル |
|------|---------|
| Modify | `types/index.ts` |
| Create | `api/splitGroupApi.ts` |
| Create | `context/SplitGroupContext.tsx` |
| Create | `components/SplitGroupList.tsx` |
| Create | `components/SplitGroupForm.tsx` |
| Create | `pages/SplitGroup.tsx` |
| Create | `components/ShareDialog.tsx` |
| Modify | `pages/Home.tsx` |
| Modify | `components/common/SideBar.tsx` |
| Modify | `routes/router.tsx` |

---

## Task 1: DB マイグレーション作成・実行

**Files:**
- Create: `src/database/migrations/*_create_split_groups_table.php`
- Create: `src/database/migrations/*_create_split_group_settings_table.php`
- Create: `src/database/migrations/*_create_split_group_category_overrides_table.php`

- [ ] **Step 1: マイグレーションファイルを3本生成**

```bash
cd src
php artisan make:migration create_split_groups_table
php artisan make:migration create_split_group_settings_table
php artisan make:migration create_split_group_category_overrides_table
```

- [ ] **Step 2: `create_split_groups_table` を実装**

```php
public function up(): void
{
    Schema::create('split_groups', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->string('label', 100);
        $table->boolean('is_active')->default(true);
        $table->timestamps();

        $table->index(['user_id', 'is_active']);
    });
}

public function down(): void
{
    Schema::dropIfExists('split_groups');
}
```

- [ ] **Step 3: `create_split_group_settings_table` を実装**

```php
public function up(): void
{
    Schema::create('split_group_settings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('split_group_id')->constrained('split_groups')->onDelete('cascade');
        $table->unsignedTinyInteger('income_other_ratio')->nullable();
        $table->unsignedTinyInteger('expense_other_ratio')->nullable();
        $table->timestamps();

        $table->unique('split_group_id');
    });
}

public function down(): void
{
    Schema::dropIfExists('split_group_settings');
}
```

- [ ] **Step 4: `create_split_group_category_overrides_table` を実装**

```php
public function up(): void
{
    Schema::create('split_group_category_overrides', function (Blueprint $table) {
        $table->id();
        $table->foreignId('split_group_id')->constrained('split_groups')->onDelete('cascade');
        $table->unsignedBigInteger('category_id');
        $table->unsignedTinyInteger('type_id');
        $table->unsignedTinyInteger('other_ratio');
        $table->timestamps();

        $table->unique(['split_group_id', 'category_id', 'type_id'], 'split_group_category_unique');
    });
}

public function down(): void
{
    Schema::dropIfExists('split_group_category_overrides');
}
```

- [ ] **Step 5: マイグレーション実行**

```bash
php artisan migrate
```

Expected: `split_groups`, `split_group_settings`, `split_group_category_overrides` の3テーブルが作成される

---

## Task 2: Eloquent モデル作成

**Files:**
- Create: `src/app/Models/SplitGroup.php`
- Create: `src/app/Models/SplitGroupSetting.php`
- Create: `src/app/Models/SplitGroupCategoryOverride.php`

- [ ] **Step 1: `SplitGroup.php` を作成**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SplitGroup extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'label', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setting(): HasOne
    {
        return $this->hasOne(SplitGroupSetting::class);
    }

    public function categoryOverrides(): HasMany
    {
        return $this->hasMany(SplitGroupCategoryOverride::class);
    }
}
```

- [ ] **Step 2: `SplitGroupSetting.php` を作成**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SplitGroupSetting extends Model
{
    use HasFactory;

    protected $fillable = ['split_group_id', 'income_other_ratio', 'expense_other_ratio'];

    public function splitGroup(): BelongsTo
    {
        return $this->belongsTo(SplitGroup::class);
    }
}
```

- [ ] **Step 3: `SplitGroupCategoryOverride.php` を作成**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SplitGroupCategoryOverride extends Model
{
    use HasFactory;

    protected $fillable = ['split_group_id', 'category_id', 'type_id', 'other_ratio'];

    public function splitGroup(): BelongsTo
    {
        return $this->belongsTo(SplitGroup::class);
    }
}
```

---

## Task 3: FormRequest 作成

**Files:**
- Create: `src/app/Http/Requests/StoreSplitGroupRequest.php`
- Create: `src/app/Http/Requests/UpdateSplitGroupRequest.php`
- Create: `src/app/Http/Requests/UpdateSplitGroupSettingsRequest.php`
- Create: `src/app/Http/Requests/UpdateCategoryOverridesRequest.php`

- [ ] **Step 1: `StoreSplitGroupRequest.php` を作成**

```bash
php artisan make:request StoreSplitGroupRequest
```

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSplitGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => 'required|string|max:100',
        ];
    }
}
```

- [ ] **Step 2: `UpdateSplitGroupRequest.php` を作成**

```bash
php artisan make:request UpdateSplitGroupRequest
```

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSplitGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'     => 'sometimes|string|max:100',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
```

- [ ] **Step 3: `UpdateSplitGroupSettingsRequest.php` を作成**

```bash
php artisan make:request UpdateSplitGroupSettingsRequest
```

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSplitGroupSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'income_other_ratio'  => 'nullable|integer|min:0|max:100',
            'expense_other_ratio' => 'nullable|integer|min:0|max:100',
        ];
    }
}
```

- [ ] **Step 4: `UpdateCategoryOverridesRequest.php` を作成**

```bash
php artisan make:request UpdateCategoryOverridesRequest
```

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryOverridesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'overrides'               => 'present|array',
            'overrides.*.category_id' => 'required|integer|min:1',
            'overrides.*.type_id'     => 'required|integer|in:1,2',
            'overrides.*.other_ratio' => 'required|integer|min:0|max:100',
        ];
    }
}
```

---

## Task 4: SplitGroupController 作成

**Files:**
- Create: `src/app/Http/Controllers/SplitGroupController.php`

- [ ] **Step 1: コントローラーファイルを作成**

```bash
php artisan make:controller SplitGroupController
```

- [ ] **Step 2: CRUD + Settings + Preview メソッドを実装**

`src/app/Http/Controllers/SplitGroupController.php` を以下の内容で実装する:

```php
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

        // settings レコードを自動作成（income_other_ratio / expense_other_ratio は NULL）
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
            $request->only(['income_other_ratio', 'expense_other_ratio'])
        );
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

        // 既存の上書き設定を全削除してから再登録（一括置換）
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

        // 収入の按分（income_other_ratio が NULL なら出力しない）
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

            $result['income'] = [
                'total'       => $incomeTotal,
                'self'        => $incomeTotal - $incomeOther,
                'other'       => $incomeOther,
                'self_ratio'  => 100 - $setting->income_other_ratio,
                'other_ratio' => $setting->income_other_ratio,
            ];
        }

        // 支出の按分（expense_other_ratio が NULL なら出力しない）
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

            $result['expense'] = [
                'total'       => $expenseTotal,
                'self'        => $expenseTotal - $expenseOther,
                'other'       => $expenseOther,
                'self_ratio'  => 100 - $setting->expense_other_ratio,
                'other_ratio' => $setting->expense_other_ratio,
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
```

---

## Task 5: APIルート追加

**Files:**
- Modify: `src/routes/api.php`

- [ ] **Step 1: `auth:sanctum` グループ内に以下を追加**

既存の `Route::delete('/fixed-expenses/{fixedExpense}', ...)` の行の直後に追記する:

```php
// 分担グループ
Route::prefix('split-groups')->group(function () {
    Route::get('/', [SplitGroupController::class, 'index']);
    Route::post('/', [SplitGroupController::class, 'store']);
    Route::put('/{splitGroup}', [SplitGroupController::class, 'update']);
    Route::delete('/{splitGroup}', [SplitGroupController::class, 'destroy']);
    Route::put('/{splitGroup}/settings', [SplitGroupController::class, 'updateSettings']);
    Route::get('/{splitGroup}/category-overrides', [SplitGroupController::class, 'getCategoryOverrides']);
    Route::put('/{splitGroup}/category-overrides', [SplitGroupController::class, 'updateCategoryOverrides']);
    Route::get('/{splitGroup}/preview', [SplitGroupController::class, 'preview']);
});
```

- [ ] **Step 2: ファイル先頭の `use` 宣言に追加**

```php
use App\Http\Controllers\SplitGroupController;
```

---

## Task 6: Feature テスト作成・実行

**Files:**
- Create: `src/tests/Feature/SplitGroupTest.php`

- [ ] **Step 1: テストファイルを作成**

```bash
php artisan make:test SplitGroupTest
```

- [ ] **Step 2: テストを実装**

```php
<?php

namespace Tests\Feature;

use App\Models\SplitGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SplitGroupTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        DB::table('types')->insert([
            ['id' => 1, 'name' => '収入', 'en_name' => 'income'],
            ['id' => 2, 'name' => '支出', 'en_name' => 'expense'],
        ]);
    }

    public function test_can_list_split_groups(): void
    {
        $group = SplitGroup::create(['user_id' => $this->user->id, 'label' => 'パートナー']);
        $group->setting()->create([]);

        $response = $this->actingAs($this->user)->getJson('/api/split-groups');

        $response->assertStatus(200)
            ->assertJsonPath('status', 200)
            ->assertJsonCount(1, 'splitGroups');
    }

    public function test_can_create_split_group(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/split-groups', [
            'label' => 'パートナー',
        ]);

        $response->assertStatus(201)->assertJsonPath('status', 200);
        $this->assertDatabaseHas('split_groups', ['user_id' => $this->user->id, 'label' => 'パートナー']);
        $this->assertDatabaseHas('split_group_settings', []);
    }

    public function test_can_update_split_group(): void
    {
        $group = SplitGroup::create(['user_id' => $this->user->id, 'label' => 'パートナー']);

        $response = $this->actingAs($this->user)->putJson("/api/split-groups/{$group->id}", [
            'label' => 'ルームメイト',
        ]);

        $response->assertStatus(200)->assertJsonPath('splitGroup.label', 'ルームメイト');
        $this->assertDatabaseHas('split_groups', ['id' => $group->id, 'label' => 'ルームメイト']);
    }

    public function test_cannot_update_other_users_split_group(): void
    {
        $otherUser = User::factory()->create();
        $group = SplitGroup::create(['user_id' => $otherUser->id, 'label' => '他人のグループ']);

        $response = $this->actingAs($this->user)->putJson("/api/split-groups/{$group->id}", [
            'label' => '改ざん',
        ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_split_group(): void
    {
        $group = SplitGroup::create(['user_id' => $this->user->id, 'label' => 'パートナー']);

        $response = $this->actingAs($this->user)->deleteJson("/api/split-groups/{$group->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('split_groups', ['id' => $group->id]);
    }

    public function test_can_update_settings(): void
    {
        $group = SplitGroup::create(['user_id' => $this->user->id, 'label' => 'パートナー']);
        $group->setting()->create([]);

        $response = $this->actingAs($this->user)->putJson("/api/split-groups/{$group->id}/settings", [
            'income_other_ratio'  => 40,
            'expense_other_ratio' => 50,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('split_group_settings', [
            'split_group_id'      => $group->id,
            'income_other_ratio'  => 40,
            'expense_other_ratio' => 50,
        ]);
    }

    public function test_can_update_category_overrides(): void
    {
        $group = SplitGroup::create(['user_id' => $this->user->id, 'label' => 'パートナー']);

        $response = $this->actingAs($this->user)->putJson("/api/split-groups/{$group->id}/category-overrides", [
            'overrides' => [
                ['category_id' => 1, 'type_id' => 2, 'other_ratio' => 70],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('split_group_category_overrides', [
            'split_group_id' => $group->id,
            'category_id'    => 1,
            'other_ratio'    => 70,
        ]);
    }

    public function test_preview_returns_correct_split(): void
    {
        $group = SplitGroup::create(['user_id' => $this->user->id, 'label' => 'パートナー']);
        $group->setting()->create(['income_other_ratio' => 40, 'expense_other_ratio' => 50]);

        // 収入 100,000円 を登録
        DB::table('income_categories')->insert([
            'id' => 1, 'user_id' => $this->user->id, 'type_id' => 1,
            'content' => '給与', 'icon' => '', 'deleted' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('contents')->insert([
            'user_id' => $this->user->id, 'type_id' => 1, 'category_id' => 1,
            'amount' => 100000, 'content' => '給与', 'recorded_at' => '2026-05-15 00:00:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/split-groups/{$group->id}/preview?month=202605");

        $response->assertStatus(200)
            ->assertJsonPath('income.total', 100000)
            ->assertJsonPath('income.other', 40000)  // floor(100000 * 40 / 100)
            ->assertJsonPath('income.self', 60000);
    }

    public function test_preview_excludes_unconfigured_types(): void
    {
        $group = SplitGroup::create(['user_id' => $this->user->id, 'label' => 'パートナー']);
        // income_other_ratio のみ設定（expense は NULL）
        $group->setting()->create(['income_other_ratio' => 40, 'expense_other_ratio' => null]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/split-groups/{$group->id}/preview?month=202605");

        $response->assertStatus(200)
            ->assertJsonMissing(['expense'])
            ->assertJsonMissing(['balance']);
    }
}
```

- [ ] **Step 3: テストを実行して全パスを確認**

```bash
php artisan test tests/Feature/SplitGroupTest.php
```

Expected: 8 tests, 8 passed

---

## Task 7: フロントエンド型定義追加

**Files:**
- Modify: `src/types/index.ts`

- [ ] **Step 1: `types/index.ts` の末尾に追加**

```typescript
export interface SplitGroup {
    id: number;
    label: string;
    is_active: boolean;
    setting: SplitGroupSetting | null;
    category_overrides: SplitGroupCategoryOverride[];
}

export interface SplitGroupSetting {
    income_other_ratio: number | null;
    expense_other_ratio: number | null;
}

export interface SplitGroupCategoryOverride {
    category_id: number;
    type_id: number;
    other_ratio: number;
}

export interface SplitGroupFormData {
    label: string;
}

export interface SplitPreview {
    group_label: string;
    month: string;
    income?: {
        total: number;
        self: number;
        other: number;
        self_ratio: number;
        other_ratio: number;
    };
    expense?: {
        total: number;
        self: number;
        other: number;
        self_ratio: number;
        other_ratio: number;
    };
    balance?: {
        total: number;
        self: number;
        other: number;
    };
}
```

---

## Task 8: splitGroupApi.ts 作成

**Files:**
- Create: `src/api/splitGroupApi.ts`

- [ ] **Step 1: `src/api/splitGroupApi.ts` を作成**

```typescript
import apiClient from '../utils/axios';
import type {
    SplitGroup,
    SplitGroupCategoryOverride,
    SplitGroupFormData,
    SplitPreview,
} from '../types';

export const splitGroupApi = {
    getAll: () =>
        apiClient.get<{ status: number; splitGroups: SplitGroup[] }>('/split-groups'),

    create: (data: SplitGroupFormData) =>
        apiClient.post<{ status: number; splitGroup: SplitGroup }>('/split-groups', data),

    update: (id: number, data: Partial<SplitGroupFormData> & { is_active?: boolean }) =>
        apiClient.put<{ status: number; splitGroup: SplitGroup }>(`/split-groups/${id}`, data),

    remove: (id: number) =>
        apiClient.delete(`/split-groups/${id}`),

    updateSettings: (
        id: number,
        data: { income_other_ratio: number | null; expense_other_ratio: number | null }
    ) =>
        apiClient.put<{ status: number; splitGroup: SplitGroup }>(
            `/split-groups/${id}/settings`,
            data
        ),

    getCategoryOverrides: (id: number) =>
        apiClient.get<{ status: number; categoryOverrides: SplitGroupCategoryOverride[] }>(
            `/split-groups/${id}/category-overrides`
        ),

    updateCategoryOverrides: (id: number, overrides: SplitGroupCategoryOverride[]) =>
        apiClient.put<{ status: number; categoryOverrides: SplitGroupCategoryOverride[] }>(
            `/split-groups/${id}/category-overrides`,
            { overrides }
        ),

    getPreview: (id: number, month: string) =>
        apiClient.get<{ status: number } & SplitPreview>(
            `/split-groups/${id}/preview?month=${month}`
        ),
};
```

---

## Task 9: SplitGroupContext.tsx 作成

**Files:**
- Create: `src/context/SplitGroupContext.tsx`

- [ ] **Step 1: `src/context/SplitGroupContext.tsx` を作成**

```tsx
import React, {
    ReactNode,
    createContext,
    useContext,
    useEffect,
    useState,
    useCallback,
} from 'react';
import type { SplitGroup, SplitGroupCategoryOverride, SplitGroupFormData } from '../types';
import { splitGroupApi } from '../api/splitGroupApi';
import { useAuthContext } from './AuthContext';
import { useAppContext } from './AppContext';

interface SplitGroupContextType {
    splitGroups: SplitGroup[];
    isLoading: boolean;
    fetchSplitGroups: () => Promise<void>;
    addSplitGroup: (data: SplitGroupFormData) => Promise<void>;
    editSplitGroup: (
        id: number,
        data: Partial<SplitGroupFormData> & { is_active?: boolean }
    ) => Promise<void>;
    saveSplitGroupSettings: (
        id: number,
        data: { income_other_ratio: number | null; expense_other_ratio: number | null }
    ) => Promise<void>;
    saveCategoryOverrides: (
        id: number,
        overrides: SplitGroupCategoryOverride[]
    ) => Promise<void>;
    removeSplitGroup: (id: number) => Promise<void>;
}

const SplitGroupContext = createContext<SplitGroupContextType | undefined>(undefined);

export const SplitGroupProvider = ({ children }: { children: ReactNode }) => {
    const { loginUser } = useAuthContext();
    const { showSnackBar } = useAppContext();
    const [splitGroups, setSplitGroups] = useState<SplitGroup[]>([]);
    const [isLoading, setIsLoading] = useState(false);

    const fetchSplitGroups = useCallback(async () => {
        if (!loginUser) return;
        setIsLoading(true);
        try {
            const { data } = await splitGroupApi.getAll();
            setSplitGroups(data.splitGroups);
        } finally {
            setIsLoading(false);
        }
    }, [loginUser]);

    const addSplitGroup = useCallback(
        async (data: SplitGroupFormData) => {
            if (!loginUser) return;
            try {
                await splitGroupApi.create(data);
                showSnackBar({ title: '成功', bodyText: '分担グループを作成しました' });
                await fetchSplitGroups();
            } catch {
                showSnackBar({
                    title: 'エラー',
                    bodyText: '分担グループの作成に失敗しました',
                    backgroundColor: '#d32f2f',
                });
            }
        },
        [loginUser, showSnackBar, fetchSplitGroups]
    );

    const editSplitGroup = useCallback(
        async (id: number, data: Partial<SplitGroupFormData> & { is_active?: boolean }) => {
            if (!loginUser) return;
            try {
                await splitGroupApi.update(id, data);
                showSnackBar({ title: '成功', bodyText: '分担グループを更新しました' });
                await fetchSplitGroups();
            } catch {
                showSnackBar({
                    title: 'エラー',
                    bodyText: '分担グループの更新に失敗しました',
                    backgroundColor: '#d32f2f',
                });
            }
        },
        [loginUser, showSnackBar, fetchSplitGroups]
    );

    const saveSplitGroupSettings = useCallback(
        async (
            id: number,
            data: { income_other_ratio: number | null; expense_other_ratio: number | null }
        ) => {
            if (!loginUser) return;
            try {
                await splitGroupApi.updateSettings(id, data);
                showSnackBar({ title: '成功', bodyText: '設定を保存しました' });
                await fetchSplitGroups();
            } catch {
                showSnackBar({
                    title: 'エラー',
                    bodyText: '設定の保存に失敗しました',
                    backgroundColor: '#d32f2f',
                });
            }
        },
        [loginUser, showSnackBar, fetchSplitGroups]
    );

    const saveCategoryOverrides = useCallback(
        async (id: number, overrides: SplitGroupCategoryOverride[]) => {
            if (!loginUser) return;
            try {
                await splitGroupApi.updateCategoryOverrides(id, overrides);
                showSnackBar({ title: '成功', bodyText: 'カテゴリ別設定を保存しました' });
                await fetchSplitGroups();
            } catch {
                showSnackBar({
                    title: 'エラー',
                    bodyText: 'カテゴリ別設定の保存に失敗しました',
                    backgroundColor: '#d32f2f',
                });
            }
        },
        [loginUser, showSnackBar, fetchSplitGroups]
    );

    const removeSplitGroup = useCallback(
        async (id: number) => {
            if (!loginUser) return;
            try {
                await splitGroupApi.remove(id);
                showSnackBar({ title: '成功', bodyText: '分担グループを削除しました' });
                await fetchSplitGroups();
            } catch {
                showSnackBar({
                    title: 'エラー',
                    bodyText: '分担グループの削除に失敗しました',
                    backgroundColor: '#d32f2f',
                });
            }
        },
        [loginUser, showSnackBar, fetchSplitGroups]
    );

    useEffect(() => {
        fetchSplitGroups();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [loginUser?.id]);

    return (
        <SplitGroupContext.Provider
            value={{
                splitGroups,
                isLoading,
                fetchSplitGroups,
                addSplitGroup,
                editSplitGroup,
                saveSplitGroupSettings,
                saveCategoryOverrides,
                removeSplitGroup,
            }}
        >
            {children}
        </SplitGroupContext.Provider>
    );
};

export const useSplitGroupContext = () => {
    const context = useContext(SplitGroupContext);
    if (!context) {
        throw new Error('useSplitGroupContext は SplitGroupProvider 内で使用してください。');
    }
    return context;
};
```

---

## Task 10: SplitGroupList.tsx 作成

**Files:**
- Create: `src/components/SplitGroupList.tsx`

- [ ] **Step 1: `src/components/SplitGroupList.tsx` を作成**

```tsx
import React from 'react';
import {
    Box,
    Chip,
    IconButton,
    Paper,
    Switch,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Typography,
} from '@mui/material';
import DeleteIcon from '@mui/icons-material/Delete';
import EditIcon from '@mui/icons-material/Edit';
import type { SplitGroup } from '../types';

interface SplitGroupListProps {
    splitGroups: SplitGroup[];
    onEdit: (group: SplitGroup) => void;
    onDelete: (id: number) => Promise<void>;
    onToggleActive: (id: number, isActive: boolean) => Promise<void>;
}

const ratioLabel = (ratio: number | null | undefined): string => {
    if (ratio == null) return '未設定';
    return `${100 - ratio}% / ${ratio}%`;
};

export const SplitGroupList = ({
    splitGroups,
    onEdit,
    onDelete,
    onToggleActive,
}: SplitGroupListProps) => {
    if (splitGroups.length === 0) {
        return (
            <Typography color="text.secondary" sx={{ mt: 2, textAlign: 'center' }}>
                分担グループがありません。「追加」ボタンから作成してください。
            </Typography>
        );
    }

    return (
        <TableContainer component={Paper} sx={{ mt: 2 }}>
            <Table size="small">
                <TableHead>
                    <TableRow>
                        <TableCell>グループ名</TableCell>
                        <TableCell>収入（自分/グループ）</TableCell>
                        <TableCell>支出（自分/グループ）</TableCell>
                        <TableCell>有効</TableCell>
                        <TableCell align="right">操作</TableCell>
                    </TableRow>
                </TableHead>
                <TableBody>
                    {splitGroups.map((group) => (
                        <TableRow key={group.id}>
                            <TableCell>
                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                    {group.label}
                                    {group.category_overrides.length > 0 && (
                                        <Chip
                                            label={`カテゴリ設定 ${group.category_overrides.length}件`}
                                            size="small"
                                            variant="outlined"
                                        />
                                    )}
                                </Box>
                            </TableCell>
                            <TableCell>
                                {ratioLabel(group.setting?.income_other_ratio)}
                            </TableCell>
                            <TableCell>
                                {ratioLabel(group.setting?.expense_other_ratio)}
                            </TableCell>
                            <TableCell>
                                <Switch
                                    checked={group.is_active}
                                    onChange={(e) => onToggleActive(group.id, e.target.checked)}
                                    size="small"
                                />
                            </TableCell>
                            <TableCell align="right">
                                <IconButton size="small" onClick={() => onEdit(group)}>
                                    <EditIcon fontSize="small" />
                                </IconButton>
                                <IconButton
                                    size="small"
                                    color="error"
                                    onClick={() => onDelete(group.id)}
                                >
                                    <DeleteIcon fontSize="small" />
                                </IconButton>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </TableContainer>
    );
};
```

---

## Task 11: SplitGroupForm.tsx 作成

**Files:**
- Create: `src/components/SplitGroupForm.tsx`

- [ ] **Step 1: `src/components/SplitGroupForm.tsx` を作成**

```tsx
import React, { useEffect, useState } from 'react';
import {
    Accordion,
    AccordionDetails,
    AccordionSummary,
    Box,
    Button,
    Checkbox,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    FormControlLabel,
    Stack,
    TextField,
    Typography,
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import LoadingButton from '@mui/lab/LoadingButton';
import { Controller, useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import type { SplitGroup, SplitGroupCategoryOverride } from '../types';
import { useAppContext } from '../context/AppContext';

const schema = z.object({
    label: z.string().min(1, 'グループ名を入力してください').max(100),
    income_other_ratio: z.number().int().min(0).max(100).nullable(),
    expense_other_ratio: z.number().int().min(0).max(100).nullable(),
});

type FormInputs = z.infer<typeof schema>;

interface SplitGroupFormProps {
    open: boolean;
    editTarget: SplitGroup | null;
    onClose: () => void;
    onSubmit: (
        label: string,
        settings: { income_other_ratio: number | null; expense_other_ratio: number | null },
        overrides: SplitGroupCategoryOverride[]
    ) => Promise<void>;
}

export const SplitGroupForm = ({
    open,
    editTarget,
    onClose,
    onSubmit,
}: SplitGroupFormProps) => {
    const { IncomeCategories, ExpenseCategories, getIncomeCategory, getExpenseCategory } =
        useAppContext();
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [overrides, setOverrides] = useState<SplitGroupCategoryOverride[]>([]);

    const {
        control,
        handleSubmit,
        reset,
        watch,
        setValue,
        formState: { errors },
    } = useForm<FormInputs>({
        resolver: zodResolver(schema),
        defaultValues: {
            label: '',
            income_other_ratio: null,
            expense_other_ratio: null,
        },
    });

    useEffect(() => {
        if (open) {
            getIncomeCategory();
            getExpenseCategory();
            if (editTarget) {
                reset({
                    label: editTarget.label,
                    income_other_ratio: editTarget.setting?.income_other_ratio ?? null,
                    expense_other_ratio: editTarget.setting?.expense_other_ratio ?? null,
                });
                setOverrides(editTarget.category_overrides ?? []);
            } else {
                reset({ label: '', income_other_ratio: null, expense_other_ratio: null });
                setOverrides([]);
            }
        }
    }, [open, editTarget]); // eslint-disable-line react-hooks/exhaustive-deps

    const incomeRatio = watch('income_other_ratio');
    const expenseRatio = watch('expense_other_ratio');

    const getOverrideRatio = (categoryId: number, typeId: number): number | null => {
        const found = overrides.find(
            (o) => o.category_id === categoryId && o.type_id === typeId
        );
        return found ? found.other_ratio : null;
    };

    const setOverrideRatio = (categoryId: number, typeId: number, ratio: number | null) => {
        setOverrides((prev) => {
            const filtered = prev.filter(
                (o) => !(o.category_id === categoryId && o.type_id === typeId)
            );
            if (ratio === null) return filtered;
            return [...filtered, { category_id: categoryId, type_id: typeId, other_ratio: ratio }];
        });
    };

    const handleFormSubmit = async (values: FormInputs) => {
        setIsSubmitting(true);
        try {
            await onSubmit(
                values.label,
                {
                    income_other_ratio: values.income_other_ratio,
                    expense_other_ratio: values.expense_other_ratio,
                },
                overrides
            );
            onClose();
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <Dialog open={open} onClose={onClose} fullWidth maxWidth="sm">
            <DialogTitle>
                {editTarget ? '分担グループを編集' : '分担グループを追加'}
            </DialogTitle>
            <DialogContent>
                <Stack spacing={3} sx={{ mt: 1 }}>
                    {/* ラベル */}
                    <Controller
                        name="label"
                        control={control}
                        render={({ field }) => (
                            <TextField
                                {...field}
                                label="グループ名"
                                placeholder="例：パートナー、ルームメイト"
                                error={!!errors.label}
                                helperText={errors.label?.message}
                                fullWidth
                            />
                        )}
                    />

                    {/* 収入割合 */}
                    <Box>
                        <Typography variant="subtitle2" gutterBottom>
                            収入の按分（グループ側の割合 %）
                        </Typography>
                        <Stack direction="row" spacing={2} alignItems="center">
                            <FormControlLabel
                                control={
                                    <Checkbox
                                        checked={incomeRatio === null}
                                        onChange={(e) =>
                                            setValue(
                                                'income_other_ratio',
                                                e.target.checked ? null : 50
                                            )
                                        }
                                    />
                                }
                                label="未設定（共有テキストに表示しない）"
                            />
                            {incomeRatio !== null && (
                                <Controller
                                    name="income_other_ratio"
                                    control={control}
                                    render={({ field }) => (
                                        <TextField
                                            {...field}
                                            label="グループ側 %"
                                            type="number"
                                            inputProps={{ min: 0, max: 100 }}
                                            size="small"
                                            sx={{ width: 120 }}
                                            onChange={(e) =>
                                                field.onChange(
                                                    e.target.value === ''
                                                        ? null
                                                        : Number(e.target.value)
                                                )
                                            }
                                            value={field.value ?? ''}
                                        />
                                    )}
                                />
                            )}
                            {incomeRatio !== null && (
                                <Typography variant="body2" color="text.secondary">
                                    自分：{100 - incomeRatio}% / グループ：{incomeRatio}%
                                </Typography>
                            )}
                        </Stack>
                    </Box>

                    {/* 支出割合 */}
                    <Box>
                        <Typography variant="subtitle2" gutterBottom>
                            支出の按分（グループ側の割合 %）
                        </Typography>
                        <Stack direction="row" spacing={2} alignItems="center">
                            <FormControlLabel
                                control={
                                    <Checkbox
                                        checked={expenseRatio === null}
                                        onChange={(e) =>
                                            setValue(
                                                'expense_other_ratio',
                                                e.target.checked ? null : 50
                                            )
                                        }
                                    />
                                }
                                label="未設定（共有テキストに表示しない）"
                            />
                            {expenseRatio !== null && (
                                <Controller
                                    name="expense_other_ratio"
                                    control={control}
                                    render={({ field }) => (
                                        <TextField
                                            {...field}
                                            label="グループ側 %"
                                            type="number"
                                            inputProps={{ min: 0, max: 100 }}
                                            size="small"
                                            sx={{ width: 120 }}
                                            onChange={(e) =>
                                                field.onChange(
                                                    e.target.value === ''
                                                        ? null
                                                        : Number(e.target.value)
                                                )
                                            }
                                            value={field.value ?? ''}
                                        />
                                    )}
                                />
                            )}
                            {expenseRatio !== null && (
                                <Typography variant="body2" color="text.secondary">
                                    自分：{100 - expenseRatio}% / グループ：{expenseRatio}%
                                </Typography>
                            )}
                        </Stack>
                    </Box>

                    {/* カテゴリ別上書き設定 */}
                    <Accordion>
                        <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                            <Typography variant="subtitle2">
                                カテゴリ別詳細設定（任意）
                            </Typography>
                        </AccordionSummary>
                        <AccordionDetails>
                            <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
                                カテゴリごとに基本設定と異なる割合を設定できます。
                                空欄は基本設定を使用します。
                            </Typography>
                            {/* 収入カテゴリ */}
                            {IncomeCategories && IncomeCategories.length > 0 && (
                                <Box sx={{ mb: 2 }}>
                                    <Typography variant="caption" color="text.secondary">
                                        収入カテゴリ
                                    </Typography>
                                    <Stack spacing={1} sx={{ mt: 0.5 }}>
                                        {IncomeCategories.map((cat) => (
                                            <Stack
                                                key={cat.id}
                                                direction="row"
                                                spacing={2}
                                                alignItems="center"
                                            >
                                                <Typography sx={{ minWidth: 120 }}>
                                                    {cat.icon} {cat.label}
                                                </Typography>
                                                <TextField
                                                    label="グループ側 %"
                                                    type="number"
                                                    inputProps={{ min: 0, max: 100 }}
                                                    size="small"
                                                    sx={{ width: 120 }}
                                                    placeholder="基本設定"
                                                    value={
                                                        getOverrideRatio(cat.id!, 1) ?? ''
                                                    }
                                                    onChange={(e) =>
                                                        setOverrideRatio(
                                                            cat.id!,
                                                            1,
                                                            e.target.value === ''
                                                                ? null
                                                                : Number(e.target.value)
                                                        )
                                                    }
                                                />
                                            </Stack>
                                        ))}
                                    </Stack>
                                </Box>
                            )}
                            {/* 支出カテゴリ */}
                            {ExpenseCategories && ExpenseCategories.length > 0 && (
                                <Box>
                                    <Typography variant="caption" color="text.secondary">
                                        支出カテゴリ
                                    </Typography>
                                    <Stack spacing={1} sx={{ mt: 0.5 }}>
                                        {ExpenseCategories.map((cat) => (
                                            <Stack
                                                key={cat.id}
                                                direction="row"
                                                spacing={2}
                                                alignItems="center"
                                            >
                                                <Typography sx={{ minWidth: 120 }}>
                                                    {cat.icon} {cat.label}
                                                </Typography>
                                                <TextField
                                                    label="グループ側 %"
                                                    type="number"
                                                    inputProps={{ min: 0, max: 100 }}
                                                    size="small"
                                                    sx={{ width: 120 }}
                                                    placeholder="基本設定"
                                                    value={
                                                        getOverrideRatio(cat.id!, 2) ?? ''
                                                    }
                                                    onChange={(e) =>
                                                        setOverrideRatio(
                                                            cat.id!,
                                                            2,
                                                            e.target.value === ''
                                                                ? null
                                                                : Number(e.target.value)
                                                        )
                                                    }
                                                />
                                            </Stack>
                                        ))}
                                    </Stack>
                                </Box>
                            )}
                        </AccordionDetails>
                    </Accordion>
                </Stack>
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose}>キャンセル</Button>
                <LoadingButton
                    loading={isSubmitting}
                    variant="contained"
                    onClick={handleSubmit(handleFormSubmit)}
                >
                    {editTarget ? '更新' : '作成'}
                </LoadingButton>
            </DialogActions>
        </Dialog>
    );
};
```

---

## Task 12: SplitGroup.tsx ページ作成

**Files:**
- Create: `src/pages/SplitGroup.tsx`

- [ ] **Step 1: `src/pages/SplitGroup.tsx` を作成**

```tsx
import React, { useState } from 'react';
import { Alert, Box, Button, CircularProgress, Typography } from '@mui/material';
import AddIcon from '@mui/icons-material/Add';
import { SplitGroupList } from '../components/SplitGroupList';
import { SplitGroupForm } from '../components/SplitGroupForm';
import { useSplitGroupContext } from '../context/SplitGroupContext';
import type { SplitGroup as SplitGroupType, SplitGroupCategoryOverride } from '../types';

const SplitGroup = () => {
    const {
        splitGroups,
        isLoading,
        addSplitGroup,
        editSplitGroup,
        saveSplitGroupSettings,
        saveCategoryOverrides,
        removeSplitGroup,
    } = useSplitGroupContext();

    const [formOpen, setFormOpen] = useState(false);
    const [editTarget, setEditTarget] = useState<SplitGroupType | null>(null);

    const handleOpenAdd = () => {
        setEditTarget(null);
        setFormOpen(true);
    };

    const handleEdit = (group: SplitGroupType) => {
        setEditTarget(group);
        setFormOpen(true);
    };

    const handleSubmit = async (
        label: string,
        settings: { income_other_ratio: number | null; expense_other_ratio: number | null },
        overrides: SplitGroupCategoryOverride[]
    ) => {
        if (editTarget) {
            if (label !== editTarget.label) {
                await editSplitGroup(editTarget.id, { label });
            }
            await saveSplitGroupSettings(editTarget.id, settings);
            await saveCategoryOverrides(editTarget.id, overrides);
        } else {
            // 新規作成：まず group を作成してから settings・overrides を保存
            // addSplitGroup 後に fetchSplitGroups が走るので最新の splitGroups から取得
            await addSplitGroup({ label });
            // 再取得後の最新グループIDを取得するため、API から直接取得
            const { splitGroupApi } = await import('../api/splitGroupApi');
            const { data } = await splitGroupApi.getAll();
            const newGroup = data.splitGroups[data.splitGroups.length - 1];
            if (newGroup) {
                await saveSplitGroupSettings(newGroup.id, settings);
                await saveCategoryOverrides(newGroup.id, overrides);
            }
        }
    };

    const handleToggleActive = async (id: number, isActive: boolean) => {
        await editSplitGroup(id, { is_active: isActive });
    };

    return (
        <Box sx={{ p: { xs: 1, sm: 2 } }}>
            <Box
                sx={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    mb: 2,
                }}
            >
                <Typography variant="h5">分担管理</Typography>
                <Button variant="contained" startIcon={<AddIcon />} onClick={handleOpenAdd}>
                    追加
                </Button>
            </Box>

            <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                パートナー・ルームメイトなど、月の収支を分担して把握・共有できます。
            </Typography>

            {isLoading ? (
                <Box sx={{ display: 'flex', justifyContent: 'center', mt: 4 }}>
                    <CircularProgress />
                </Box>
            ) : (
                <SplitGroupList
                    splitGroups={splitGroups}
                    onEdit={handleEdit}
                    onDelete={removeSplitGroup}
                    onToggleActive={handleToggleActive}
                />
            )}

            <SplitGroupForm
                open={formOpen}
                editTarget={editTarget}
                onClose={() => setFormOpen(false)}
                onSubmit={handleSubmit}
            />
        </Box>
    );
};

export default SplitGroup;
```

---

## Task 13: ShareDialog.tsx 作成

**Files:**
- Create: `src/components/ShareDialog.tsx`

- [ ] **Step 1: `src/components/ShareDialog.tsx` を作成**

```tsx
import React, { useEffect, useState } from 'react';
import {
    Box,
    Button,
    Checkbox,
    CircularProgress,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Divider,
    FormControl,
    FormControlLabel,
    FormGroup,
    InputLabel,
    MenuItem,
    Paper,
    Select,
    Typography,
} from '@mui/material';
import ContentCopyIcon from '@mui/icons-material/ContentCopy';
import { format, subMonths } from 'date-fns';
import type { SplitGroup, SplitPreview } from '../types';
import { splitGroupApi } from '../api/splitGroupApi';
import { useAppContext } from '../context/AppContext';

interface ShareDialogProps {
    open: boolean;
    onClose: () => void;
    splitGroups: SplitGroup[];
}

const formatAmount = (amount: number): string =>
    amount.toLocaleString('ja-JP') + '円';

const buildShareText = (
    preview: SplitPreview,
    showIncome: boolean,
    showExpense: boolean,
    showBalance: boolean
): string => {
    const [year, monthStr] = preview.month.split('-');
    const monthLabel = `${year}年${parseInt(monthStr)}月`;
    const lines: string[] = [
        `📊 ${monthLabel}の家計まとめ【${preview.group_label}】`,
        '─────────────────────',
    ];

    if (showIncome && preview.income) {
        lines.push(`収入：${formatAmount(preview.income.total)}`);
        lines.push(`  自分：${formatAmount(preview.income.self)}（${preview.income.self_ratio}%）`);
        lines.push(`  ${preview.group_label}：${formatAmount(preview.income.other)}（${preview.income.other_ratio}%）`);
        lines.push('');
    }

    if (showExpense && preview.expense) {
        lines.push(`支出：${formatAmount(preview.expense.total)}`);
        lines.push(`  自分：${formatAmount(preview.expense.self)}（${preview.expense.self_ratio}%）`);
        lines.push(`  ${preview.group_label}：${formatAmount(preview.expense.other)}（${preview.expense.other_ratio}%）`);
        lines.push('');
    }

    if (showBalance && preview.balance) {
        lines.push(`残高：${formatAmount(preview.balance.total)}`);
        lines.push(`  自分：${formatAmount(preview.balance.self)}`);
        lines.push(`  ${preview.group_label}：${formatAmount(preview.balance.other)}`);
        lines.push('');
    }

    lines.push('─────────────────────');
    lines.push('#カケポン家計簿');

    return lines.join('\n');
};

export const ShareDialog = ({ open, onClose, splitGroups }: ShareDialogProps) => {
    const { currentMonth, showSnackBar } = useAppContext();

    const [selectedGroupId, setSelectedGroupId] = useState<number | ''>('');
    const [selectedMonth, setSelectedMonth] = useState<string>(
        format(currentMonth, 'yyyyMM')
    );
    const [preview, setPreview] = useState<SplitPreview | null>(null);
    const [isLoadingPreview, setIsLoadingPreview] = useState(false);

    const [showIncome, setShowIncome] = useState(true);
    const [showExpense, setShowExpense] = useState(true);
    const [showBalance, setShowBalance] = useState(true);

    // 月選択肢：現在月を含む過去12ヶ月
    const monthOptions = Array.from({ length: 12 }, (_, i) => {
        const d = subMonths(new Date(), i);
        return { label: format(d, 'yyyy年M月'), value: format(d, 'yyyyMM') };
    });

    useEffect(() => {
        if (!open) return;
        setSelectedGroupId(splitGroups[0]?.id ?? '');
        setSelectedMonth(format(currentMonth, 'yyyyMM'));
        setPreview(null);
    }, [open]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        if (!selectedGroupId || !selectedMonth) return;
        const fetchPreview = async () => {
            setIsLoadingPreview(true);
            try {
                const { data } = await splitGroupApi.getPreview(
                    selectedGroupId as number,
                    selectedMonth
                );
                setPreview(data);
            } catch {
                setPreview(null);
            } finally {
                setIsLoadingPreview(false);
            }
        };
        fetchPreview();
    }, [selectedGroupId, selectedMonth]);

    const shareText =
        preview && (showIncome || showExpense || showBalance)
            ? buildShareText(preview, showIncome, showExpense, showBalance)
            : '';

    const handleCopy = async () => {
        if (!shareText) return;
        await navigator.clipboard.writeText(shareText);
        showSnackBar({ title: 'コピーしました', bodyText: 'クリップボードにコピーしました' });
    };

    return (
        <Dialog open={open} onClose={onClose} fullWidth maxWidth="sm">
            <DialogTitle>家計を共有する</DialogTitle>
            <DialogContent>
                <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
                    {/* グループ選択 */}
                    <FormControl fullWidth size="small">
                        <InputLabel>グループ</InputLabel>
                        <Select
                            value={selectedGroupId}
                            label="グループ"
                            onChange={(e) => setSelectedGroupId(e.target.value as number)}
                        >
                            {splitGroups.map((g) => (
                                <MenuItem key={g.id} value={g.id}>
                                    {g.label}
                                </MenuItem>
                            ))}
                        </Select>
                    </FormControl>

                    {/* 月選択 */}
                    <FormControl fullWidth size="small">
                        <InputLabel>対象月</InputLabel>
                        <Select
                            value={selectedMonth}
                            label="対象月"
                            onChange={(e) => setSelectedMonth(e.target.value)}
                        >
                            {monthOptions.map((m) => (
                                <MenuItem key={m.value} value={m.value}>
                                    {m.label}
                                </MenuItem>
                            ))}
                        </Select>
                    </FormControl>

                    {/* 共有項目チェック */}
                    <Box>
                        <Typography variant="subtitle2" gutterBottom>
                            共有する項目
                        </Typography>
                        <FormGroup row>
                            <FormControlLabel
                                control={
                                    <Checkbox
                                        checked={showIncome}
                                        onChange={(e) => setShowIncome(e.target.checked)}
                                        disabled={!preview?.income}
                                    />
                                }
                                label="収入"
                            />
                            <FormControlLabel
                                control={
                                    <Checkbox
                                        checked={showExpense}
                                        onChange={(e) => setShowExpense(e.target.checked)}
                                        disabled={!preview?.expense}
                                    />
                                }
                                label="支出"
                            />
                            <FormControlLabel
                                control={
                                    <Checkbox
                                        checked={showBalance}
                                        onChange={(e) => setShowBalance(e.target.checked)}
                                        disabled={!preview?.balance}
                                    />
                                }
                                label="残高"
                            />
                        </FormGroup>
                    </Box>

                    <Divider />

                    {/* テキストプレビュー */}
                    {isLoadingPreview ? (
                        <Box sx={{ display: 'flex', justifyContent: 'center', py: 2 }}>
                            <CircularProgress size={24} />
                        </Box>
                    ) : shareText ? (
                        <Paper
                            variant="outlined"
                            sx={{ p: 2, backgroundColor: 'grey.50', whiteSpace: 'pre-wrap', fontFamily: 'monospace', fontSize: '0.85rem' }}
                        >
                            {shareText}
                        </Paper>
                    ) : (
                        <Typography color="text.secondary" variant="body2">
                            グループを選択してプレビューを表示します。
                            表示されない場合は、グループの割合設定を確認してください。
                        </Typography>
                    )}
                </Box>
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose}>閉じる</Button>
                <Button
                    variant="contained"
                    startIcon={<ContentCopyIcon />}
                    onClick={handleCopy}
                    disabled={!shareText}
                >
                    コピーする
                </Button>
            </DialogActions>
        </Dialog>
    );
};
```

---

## Task 14: Home.tsx に共有ボタンを追加

**Files:**
- Modify: `src/pages/Home.tsx`

- [ ] **Step 1: Home.tsx に共有ボタンと ShareDialog を追加**

`Home.tsx` に以下の変更を加える:

1. import を追加（ファイル先頭の import 群に追記）:

```tsx
import ShareIcon from '@mui/icons-material/Share';
import { ShareDialog } from '../components/ShareDialog';
import { useSplitGroupContext } from '../context/SplitGroupContext';
```

2. `Home` コンポーネント内の state に追加（既存の state 定義の近くに）:

```tsx
const [isShareDialogOpen, setIsShareDialogOpen] = useState(false);
const { splitGroups } = useSplitGroupContext();
```

3. `MonthlySummary` コンポーネントの直後（閉じタグの後）に追加:

```tsx
{/* 共有ボタン */}
<Box sx={{ display: 'flex', justifyContent: 'flex-end', mb: 1 }}>
    <Button
        variant="outlined"
        size="small"
        startIcon={<ShareIcon />}
        onClick={() => setIsShareDialogOpen(true)}
        disabled={splitGroups.length === 0}
    >
        共有
    </Button>
</Box>

<ShareDialog
    open={isShareDialogOpen}
    onClose={() => setIsShareDialogOpen(false)}
    splitGroups={splitGroups}
/>
```

---

## Task 15: SideBar.tsx にメニュー項目を追加

**Files:**
- Modify: `src/components/common/SideBar.tsx`

- [ ] **Step 1: import に GroupsIcon を追加**

```tsx
import GroupsIcon from '@mui/icons-material/Groups';
```

- [ ] **Step 2: `MenuItems` 配列に追加**

`{ text: '固定収支管理', path: '/fixed-expenses', icon: RepeatIcon }` の行の直後に追加:

```tsx
{ text: '分担管理', path: '/split-groups', icon: GroupsIcon },
```

---

## Task 16: router.tsx にルートを追加

**Files:**
- Modify: `src/routes/router.tsx`

- [ ] **Step 1: import を追加**

```tsx
import SplitGroup from '../pages/SplitGroup';
import { SplitGroupProvider } from '../context/SplitGroupContext';
```

- [ ] **Step 2: `/fixed-expenses` の Route の直後に追加**

```tsx
<Route
    path="/split-groups"
    element={
        <PrivateRoute>
            <SplitGroupProvider>
                <SplitGroup />
            </SplitGroupProvider>
        </PrivateRoute>
    }
/>
```

- [ ] **Step 3: Home ルートの `TransactionProvider` を `SplitGroupProvider` でラップ**

Home ページで `useSplitGroupContext` を使用するため、Home ルートのプロバイダーを更新する。
`index` ルートの `element` を以下に変更:

```tsx
element={
    <TransactionProvider>
        <SplitGroupProvider>
            <Home />
        </SplitGroupProvider>
    </TransactionProvider>
}
```

---

## Task 17: TypeScript 型チェックを実行

- [ ] **Step 1: 型チェックを実行してエラーがないことを確認**

フロントエンドリポジトリ（`C:\WorkSpace\FrontendHouseHoldExpenses`）で実行:

```bash
npm run typecheck
```

Expected: エラーなし（型エラーがあれば修正）

---

## Task 18: 最終コミット

- [ ] **Step 1: バックエンドをコミット**

```bash
cd c:\WorkSpace\HouseHoldExpenses\src
git add -A
git commit -m "feat: 分担管理＆SNS共有機能を実装（split_groups / preview / API）"
```

- [ ] **Step 2: フロントエンドをコミット**

```bash
cd C:\WorkSpace\FrontendHouseHoldExpenses
git add -A
git commit -m "feat: 分担管理ページ・共有ダイアログを実装"
```

---

## 仕様カバレッジ確認

| 仕様項目 | 対応タスク |
|----------|----------|
| split_groups テーブル | Task 1, 2 |
| split_group_settings テーブル | Task 1, 2 |
| split_group_category_overrides テーブル | Task 1, 2 |
| CRUD API | Task 4, 5 |
| 按分計算（切り捨て・カテゴリ上書き） | Task 4（preview） |
| NULL=未設定=共有テキスト非表示 | Task 4, 13 |
| 残高は両設定済み時のみ | Task 4, 13 |
| フロント型定義 | Task 7 |
| API クライアント | Task 8 |
| Context / 状態管理 | Task 9 |
| 分担設定ページ | Task 10, 11, 12 |
| 共有ダイアログ（プレビュー・チェック・コピー） | Task 13 |
| Home ページに共有ボタン | Task 14 |
| ナビゲーション追加 | Task 15 |
| ルーティング追加 | Task 16 |
| Feature テスト | Task 6 |
