# 分担管理＆SNS共有機能 設計書

**作成日**: 2026-05-26  
**ステータス**: 承認済み

---

## 概要

ユーザーが記録した月の収支合計を「家計全体」とみなし、設定した割合で「自分分 / グループ分」に按分して表示・テキスト共有できる機能。

パートナーとの家計把握を主目的としつつ、ルームメイト・個人費 vs 事業費・カテゴリ別バケット管理など汎用的に使えるよう「グループ名（ラベル）を自由に設定できる分担管理」として設計する。

---

## 他用途の想定

| 用途 | ラベル例 |
|------|---------|
| パートナーとの按分確認 | パートナー |
| 同居人との生活費割り勘 | ルームメイト |
| 個人費と事業費の分離 | 事業費 |
| 目的別バケット管理 | 貯蓄用 |
| 家族への説明 | 家族 |

---

## ユーザーフロー

```
[設定画面 /split-groups]
  └─ グループ作成（ラベル名 + 基本割合設定）
       └─ 詳細設定（カテゴリ別割合の上書き）

[ホーム or レポート画面]
  └─ 「共有」ボタン
       └─ グループ選択
            └─ 対象月選択
                 └─ プレビュー表示（按分結果）
                      └─ 共有項目チェックボックス（プライバシー設定）
                           ├─ [ ] 収入
                           ├─ [ ] 支出
                           └─ [ ] 残高
                                └─ テキスト生成 → クリップボードにコピー
                                     └─ LINE / X / メール等に貼り付け
```

---

## 按分ロジック

### 基本ルール

- 月の収入合計 × `income_other_ratio / 100` = グループ分収入
- 月の支出合計 × `expense_other_ratio / 100` = グループ分支出
- 自分分 = 合計 − グループ分
- 残高 = 按分後の「自分収入 − 自分支出」/ 「グループ収入 − グループ支出」

### カテゴリ上書きルール

- `split_group_category_overrides` に登録されたカテゴリは、そのカテゴリの合計に対して上書き割合を適用
- 上書きなしのカテゴリは基本割合を適用
- カテゴリ別に計算後、合算して月の按分結果を算出

### 共有テキスト出力ルール

- `income_other_ratio` が NULL → 収入セクションは出力しない
- `expense_other_ratio` が NULL → 支出セクションは出力しない
- 残高は収入・支出の両方が設定済みの場合のみ出力
- ユーザーがチェックボックスで非表示にした項目も出力しない

### 共有テキストフォーマット例

```
📊 2026年5月の家計まとめ【パートナー】
─────────────────────
収入：300,000円
  自分：180,000円（60%）
  パートナー：120,000円（40%）

支出：200,000円
  自分：120,000円（60%）
  パートナー：80,000円（40%）

残高：100,000円
  自分：60,000円
  パートナー：40,000円
─────────────────────
#カケポン家計簿
```

---

## データモデル

### 新規テーブル（既存テーブルへの変更なし）

#### `split_groups`（分担グループ）

| カラム | 型 | NULL | 説明 |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | NO | |
| user_id | BIGINT UNSIGNED FK | NO | users テーブル |
| label | VARCHAR(100) | NO | グループ名（例：パートナー） |
| is_active | BOOLEAN | NO | 有効/無効フラグ（DEFAULT true） |
| created_at | TIMESTAMP | YES | |
| updated_at | TIMESTAMP | YES | |

**インデックス**:
- PRIMARY KEY (id)
- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
- KEY (user_id, is_active)

#### `split_group_settings`（グループ設定）

| カラム | 型 | NULL | 説明 |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | NO | |
| split_group_id | BIGINT UNSIGNED FK | NO | split_groups |
| income_other_ratio | TINYINT UNSIGNED | YES | 収入のグループ側割合（0〜100）。NULL = 設定なし |
| expense_other_ratio | TINYINT UNSIGNED | YES | 支出のグループ側割合（0〜100）。NULL = 設定なし |
| created_at | TIMESTAMP | YES | |
| updated_at | TIMESTAMP | YES | |

**インデックス**:
- PRIMARY KEY (id)
- UNIQUE KEY (split_group_id)
- FOREIGN KEY (split_group_id) REFERENCES split_groups(id) ON DELETE CASCADE

#### `split_group_category_overrides`（カテゴリ別上書き設定）

| カラム | 型 | NULL | 説明 |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | NO | |
| split_group_id | BIGINT UNSIGNED FK | NO | split_groups |
| category_id | BIGINT UNSIGNED | NO | income_categories or expence_categories の id |
| type_id | TINYINT UNSIGNED | NO | 1=収入 / 2=支出 |
| other_ratio | TINYINT UNSIGNED | NO | 上書き割合（0〜100） |
| created_at | TIMESTAMP | YES | |
| updated_at | TIMESTAMP | YES | |

**インデックス**:
- PRIMARY KEY (id)
- FOREIGN KEY (split_group_id) REFERENCES split_groups(id) ON DELETE CASCADE
- UNIQUE KEY (split_group_id, category_id, type_id)

---

## バックエンドAPI設計

### エンドポイント一覧

#### 分担グループ CRUD

| メソッド | パス | 説明 |
|---|---|---|
| GET | `/api/split-groups` | グループ一覧取得（設定・上書き含む） |
| POST | `/api/split-groups` | グループ作成 |
| PUT | `/api/split-groups/{id}` | グループ更新（ラベル・有効フラグ） |
| DELETE | `/api/split-groups/{id}` | グループ削除 |

#### グループ設定

| メソッド | パス | 説明 |
|---|---|---|
| PUT | `/api/split-groups/{id}/settings` | 基本割合設定の更新（NULL可） |
| GET | `/api/split-groups/{id}/category-overrides` | カテゴリ別上書き一覧取得 |
| PUT | `/api/split-groups/{id}/category-overrides` | カテゴリ別上書きを一括更新 |

#### 按分プレビュー（共有テキスト生成用）

| メソッド | パス | 説明 |
|---|---|---|
| GET | `/api/split-groups/{id}/preview?month=202605` | 指定月の按分計算結果を返す |

### previewレスポンス仕様

```json
{
  "group_label": "パートナー",
  "month": "2026-05",
  "income": {
    "total": 300000,
    "self": 180000,
    "other": 120000,
    "self_ratio": 60,
    "other_ratio": 40
  },
  "expense": {
    "total": 200000,
    "self": 120000,
    "other": 80000,
    "self_ratio": 60,
    "other_ratio": 40
  },
  "balance": {
    "total": 100000,
    "self": 60000,
    "other": 40000
  }
}
```

- `income` / `expense` / `balance` は設定なしの場合レスポンスに含まれない
- 按分計算はバックエンドで完結
- カテゴリ上書きがある場合はカテゴリ別計算後に合算

### 追加クラス構成

既存の Service / Repository パターンに沿って追加：

```
app/Http/Controllers/SplitGroupController.php
app/Http/Requests/StoreSplitGroupRequest.php
app/Http/Requests/UpdateSplitGroupSettingsRequest.php
app/Http/Requests/UpdateCategoryOverridesRequest.php
app/Services/SplitGroupService.php          ← 按分計算ロジック
app/Repositories/SplitGroupRepository.php
app/Interfaces/Services/SplitGroupServiceInterface.php
app/Interfaces/Repositories/SplitGroupRepositoryInterface.php
app/Models/SplitGroup.php
app/Models/SplitGroupSettings.php
app/Models/SplitGroupCategoryOverride.php
```

---

## フロントエンド画面設計

### 新規ページ

#### 分担設定ページ（`/split-groups`）

```
SplitGroupPage
  ├─ SplitGroupList           # グループ一覧カード
  │    └─ SplitGroupCard      # ラベル・有効状態・編集/削除ボタン
  └─ SplitGroupForm           # 作成・編集フォーム（ドロワーまたはダイアログ）
       ├─ ラベル入力
       ├─ 収入割合（グループ側 % or 未設定）
       ├─ 支出割合（グループ側 % or 未設定）
       └─ カテゴリ別上書き設定（アコーディオンで折り畳み）
            └─ カテゴリごとに割合入力 or 「基本設定を使用」
```

### 既存ページへの追加

#### 共有ダイアログ（`Home.tsx` または `Report.tsx` に追加）

「共有」ボタンを追加し、クリックで以下のダイアログを表示：

```
ShareDialog
  ├─ グループ選択（ドロップダウン）
  ├─ 対象月選択
  ├─ 按分プレビュー表示
  ├─ 共有項目チェックボックス
  │    ├─ [ ] 収入
  │    ├─ [ ] 支出
  │    └─ [ ] 残高
  ├─ テキストプレビュー（チェックに連動してリアルタイム更新）
  └─ 「コピーする」ボタン → クリップボードにコピー → スナックバー通知
```

### ルート追加

```
/split-groups   # 既存ナビゲーションに追加
```

### 型定義追加（`types/index.ts`）

```typescript
export interface SplitGroup {
  id: number;
  label: string;
  is_active: boolean;
  settings: SplitGroupSettings;
  category_overrides: SplitGroupCategoryOverride[];
}

export interface SplitGroupSettings {
  income_other_ratio: number | null;
  expense_other_ratio: number | null;
}

export interface SplitGroupCategoryOverride {
  category_id: number;
  type_id: number;
  other_ratio: number;
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

## 技術的制約・注意事項

- 既存テーブルへの変更なし（非侵襲的設計）
- グループ設定は永続的（毎月引き継ぎ）、変更があれば設定画面から更新
- 共有テキストはサーバーサイドで計算した値をフロントでフォーマット
- プライバシー：共有前にユーザーがチェックボックスで項目を選択
- `other_ratio` のバリデーション：0〜100 の整数
