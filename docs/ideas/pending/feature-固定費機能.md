---
name: feature-固定費機能
description: 固定費（毎月自動で複製される支出）の登録・管理機能
---

# 固定費機能

## 🎯 概要

**背景**: 家計管理では、毎月同じ金額が同じ日に発生する支出（家賃、保険、定期購読など）が多い。現状ではこれらを毎月手動で入力する必要があり、手間とミスの温床になっている。

**目標**: 「固定費」として登録した支出を毎月自動で複製（レプリケート）し、ユーザーの手動入力を削減する。また、固定費の変更（金額変更）時は、過去の記録は変わらず、未来の複製から新しい金額を適用できるようにする。

---

## 📋 機能要件

### 2.1 UI/UX 要件

#### PC版（デスクトップ）

1. **固定費登録方法①: 支出作成時**
   - 支出入力フォームに「固定費として登録」チェックボックスを追加
   ```
   ┌─────────────────────────────────┐
   │ 支出入力フォーム                 │
   │                                 │
   │ 日時: 2026-04-12               │
   │ カテゴリ: [食費▼]               │
   │ 金額: ¥30,000                   │
   │ 内容: 食費・日用品               │
   │                                 │
   │ ☐ 固定費として登録              │
   │                                 │
   │ [キャンセル]  [保存]             │
   └─────────────────────────────────┘
   ```

2. **固定費登録方法②: 専用管理画面**
   - サイドメニューに「固定費管理」メニューを追加
   - 固定費一覧表示画面を作成
   ```
   固定費管理画面
   ┌───────────────────────────────────────────────────┐
   │ 固定費管理                          [+ 追加]       │
   │                                                   │
   │ ┌─────────────────────────────────────────────┐  │
   │ │ 固定費一覧                                  │  │
   │ │                                             │  │
   │ │ □ 家賃        ¥100,000  毎月1日  [編集][削除]│  │
   │ │ □ 保険        ¥5,000    毎月15日 [編集][削除]│  │
   │ │ □ インター   ¥3,000    毎月20日 [編集][削除]│  │
   │ │                                             │  │
   │ └─────────────────────────────────────────────┘  │
   │                                                   │
   │ [有効]  [無効] フィルタ:  ○全て ○有効のみ       │
   └───────────────────────────────────────────────────┘
   ```

3. **固定費登録フォーム（方法②用）**
   ```
   ┌──────────────────────────────────┐
   │ 固定費を追加                      │
   │                                  │
   │ カテゴリ: [家賃▼]                 │
   │ 金額: ¥100,000                    │
   │ 内容: 家賃（賃貸マンション）       │
   │                                  │
   │ 毎月の実行日（日付）:             │
   │ [  29  ] 日                       │
   │ ※1～31日から選択可能             │
   │                                  │
   │ ⚠️ 注意:                          │
   │ 29日以上を指定した場合、その日   │
   │ が存在しない月は当月の最終日に   │
   │ 調整されます。                    │
   │ 例：2月に29日指定 → 2/28(平年) │
   │ 例：4月に31日指定 → 4/30         │
   │                                  │
   │ 最初の自動登録予定日:             │
   │ 2026-05-29 (自動計算)             │
   │                                  │
   │ [キャンセル]  [保存]              │
   └──────────────────────────────────┘
   ```

4. **カレンダービューでの固定費の区別**
   - 固定費から複製された支出に「固定費」バッジを表示
   ```
   ┌─────────────────────────────┐
   │ 2026-03-14 の支出            │
   │                             │
   │ 固定費  家賃    ¥100,000    │ ← バッジ付き
   │        保険    ¥5,000       │
   │                             │
   │ 通常    食費    ¥30,000     │
   │                             │
   │ [詳細表示]  [編集]          │
   └─────────────────────────────┘
   ```

5. **固定費の編集**
   - 固定費を編集する場合、「この日から変更」「すべて変更」オプションを提供
   ```
   ┌────────────────────────────────┐
   │ 固定費を編集                    │
   │                                │
   │ カテゴリ: [家賃▼]               │
   │ 金額: ¥110,000 (変更前: 100000) │
   │ 内容: 家賃                      │
   │ 実行日: 1日                     │
   │                                │
   │ 変更方法:                       │
   │ ◎ この日以降に適用 (2026-05-01) │
   │ ○ すべての記録に適用（非推奨）   │
   │                                │
   │ [キャンセル]  [保存]            │
   └────────────────────────────────┘
   ```

#### SP版（スマートフォン）

1. **固定費登録方法①: 支出作成時**
   - 同じ UI だが、チェックボックスは目立つ位置に配置

2. **固定費管理画面（SP版）**
   - 横スクロール対応の一覧表示
   - 横スワイプで削除・編集オプション表示

3. **固定費編集フォーム（SP版）**
   - モーダルダイアログで表示

---

### 2.2 機能要件

#### ユースケース1: 固定費の新規登録（方法①）

1. ユーザーが「新規支出」フォームを開く
2. カテゴリ、金額、内容を入力
3. 「固定費として登録」をチェック
4. 実行日をデフォルト（その日の日付）または変更
5. 「保存」をクリック
6. バックエンドで以下を実行：
   - `contents` テーブルに新規レコード作成（`is_fixed_expense=true`, `fixed_expense_day=指定日`）
   - 翌月から自動複製を予約（タスク発行）
7. 完了メッセージ表示 → フォームをクリア

**データベース処理**:
```
INSERT INTO contents (
  user_id, type_id, category_id, amount, content,
  recorded_at, is_fixed_expense, fixed_expense_day,
  created_at, updated_at
) VALUES (
  1, 2, 5, 100000, '家賃',
  '2026-04-01', true, 1,
  NOW(), NOW()
)
```

#### ユースケース2: 固定費の新規登録（方法②）

1. ユーザーが「固定費管理」画面を開く
2. 「+ 追加」ボタンをクリック
3. 固定費登録フォームを開く
4. カテゴリ、金額、内容、実行日（日付）を入力
5. 「保存」をクリック
6. バックエンドで以下を実行：
   - 同ユースケース1と同じ
7. 固定費一覧に追加表示

#### ユースケース3: 固定費の編集

1. ユーザーが固定費管理画面の「編集」をクリック
2. 編集フォームを開く
3. 金額・内容・実行日を変更
4. 「この日以降に適用」を選択
5. 「保存」をクリック
6. バックエンドで以下を実行：
   - `fixed_expenses_history` テーブル（または`contents`の`fixed_expense_version`）に変更履歴を記録
   - 次の複製処理から新しい金額を適用
7. 完了メッセージ表示

**重要**: 過去の記録は変わらない。今後の複製のみ新しい金額を適用

#### ユースケース4: 固定費の削除

1. ユーザーが固定費管理画面の「削除」をクリック
2. 確認ダイアログ表示：
   ```
   「この固定費を削除しますか？」
   「削除後、新しい複製は生成されません。既に登録された支出は削除されません。」
   
   [キャンセル] [削除]
   ```
3. 「削除」をクリック
4. バックエンドで以下を実行：
   - `fixed_expenses` テーブルの該当レコードを削除（または論理削除）
   - 次の複製タスクをキャンセル
5. 固定費一覧から削除

#### ユースケース5: 毎月自動複製（バックエンド処理）

**実行タイミング**: 毎月1日 朝5:00 JST（日本人が最も使用しない時間帯）

> **設計理由**: バッチ処理実行時のデータベース負荷を考慮し、日本人の利用ピーク時間帯（昼9時～18時、夜19時～23時）を避けて朝5時に設定。これにより、ユーザー体験への影響を最小化。

**流れ（バッチ処理）**:
```
1. `fixed_expenses` テーブルから全ユーザーの有効な固定費を取得
2. 各固定費について：
   a. `fixed_expense_day` を読み取り
   b. 当月の実行日を計算
      - 1～28日：そのまま使用
      - 29～31日：以下のルールで調整
        * その日が当月に存在 → その日を使用
        * その日が当月に存在しない → 当月の最終日を使用
      例：2月に29日指定 → 平年は2/28、閏年は2/29
      例：4月に31日指定 → 4/30
   c. `contents` テーブルに複製レコード作成
      - `is_fixed_expense=true`
      - `fixed_expense_id=元のID`（参照用）
      - `recorded_at=計算した実行日`
   d. 複製成功をログ出力
3. エラーハンドリング：
   - 複製に失敗したら、管理者へアラート（Slack/メール）
   - リトライ機構：失敗時は同日の朝6時に再実行
```

#### 制約事項

1. **固定費の実行日**
   - 1～31日を選択可能
   - **スマート調整機能**:
     * 1～28日：そのまま実行（全ての月に存在）
     * 29～31日：以下のルールで自動調整
       - その日が当月に存在 → その日に実行
       - その日が当月に存在しない → 当月の最終日に実行
   - **UI警告表示**: 29～31日指定時に以下のアラートを表示
     ```
     ⚠️ 注意：29日以上を指定した場合、その日が存在しない月は
     自動的に当月の最終日に調整されます。
     例) 2月に29日指定 → 平年は2/28、閏年は2/29
     例) 4月に31日指定 → 4/30に実行
     ```

2. **複製タイミング**
   - 毎月1日に翌月分を作成（例: 4月1日に5月分を作成）
   - または毎月最終日に翌月分を作成（確認：どちらがいい？）

3. **固定費の変更**
   - 「この日以降に適用」を選択時：変更日の翌月から新しい金額を適用
   - 変更日より前の複製済み記録は変更しない

4. **削除時の動作**
   - 固定費の定義を削除しても、既に複製された`contents`は削除されない
   - 削除後、新しい複製は生成されない

5. **固定費からの支出の削除**
   - 複製された支出は通常の支出と同じように削除可能
   - ただし「このシリーズ全部削除」などの一括削除機能は不要

---

## 🗄️ データベース設計

### 新規テーブル: fixed_expenses

固定費の定義を管理するテーブル

```sql
CREATE TABLE fixed_expenses (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    type_id INT NOT NULL DEFAULT 2,  -- 常に「支出」
    category_id BIGINT NOT NULL,
    amount INT NOT NULL,
    content VARCHAR(255) NOT NULL,
    fixed_expense_day INT NOT NULL,  -- 1～28（毎月の何日に実行するか）
    is_active BOOLEAN DEFAULT true,
    last_replicated_at TIMESTAMP NULL,  -- 最後に複製した日時
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,  -- ソフトデリート
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (type_id) REFERENCES types(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    INDEX idx_last_replicated (last_replicated_at)
);
```

### 既存テーブル: contents への追加カラム

```sql
ALTER TABLE contents ADD COLUMN (
    is_fixed_expense BOOLEAN DEFAULT false COMMENT '固定費として登録された支出かどうか',
    fixed_expense_day INT NULL COMMENT '固定費の場合、毎月の何日に実行するか',
    fixed_expense_id BIGINT NULL COMMENT '元になった固定費の定義ID（複製の場合）',
    
    FOREIGN KEY (fixed_expense_id) REFERENCES fixed_expenses(id) ON DELETE SET NULL,
    INDEX idx_fixed_expense (is_fixed_expense),
    INDEX idx_fixed_expense_id (fixed_expense_id)
);
```

### 新規テーブル（オプション）: fixed_expense_history

固定費の変更履歴を管理（変更前の金額との追跡用）

```sql
CREATE TABLE fixed_expense_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    fixed_expense_id BIGINT NOT NULL,
    amount INT NOT NULL,  -- この金額がいつから有効か
    effective_from TIMESTAMP NOT NULL,  -- この金額が適用される開始日
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (fixed_expense_id) REFERENCES fixed_expenses(id) ON DELETE CASCADE,
    INDEX idx_fixed_expense_id (fixed_expense_id),
    INDEX idx_effective_from (effective_from)
);
```

---

## 🔌 API 設計

### エンドポイント1: 固定費一覧取得

```
GET /api/fixed-expenses

レスポンス:
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "category_id": 5,
      "category_name": "家賃",
      "amount": 100000,
      "content": "賃貸マンション",
      "fixed_expense_day": 1,
      "is_active": true,
      "last_replicated_at": "2026-03-01T00:05:00Z",
      "created_at": "2026-01-15T10:00:00Z",
      "updated_at": "2026-01-15T10:00:00Z"
    },
    ...
  ]
}
```

### エンドポイント2: 固定費作成

```
POST /api/fixed-expenses

リクエスト:
{
  "user_id": 1,
  "category_id": 5,
  "amount": 100000,
  "content": "賃貸マンション",
  "fixed_expense_day": 1  // 1～28
}

レスポンス:
{
  "success": true,
  "message": "固定費を作成しました",
  "data": {
    "id": 1,
    "user_id": 1,
    "category_id": 5,
    "amount": 100000,
    "content": "賃貸マンション",
    "fixed_expense_day": 1,
    "is_active": true,
    "created_at": "2026-04-12T10:00:00Z",
    "updated_at": "2026-04-12T10:00:00Z"
  }
}
```

### エンドポイント3: 固定費更新

```
PUT /api/fixed-expenses/{id}

リクエスト:
{
  "amount": 110000,  // 金額変更
  "content": "家賃（家賃UP）",
  "fixed_expense_day": 1,
  "apply_to": "future"  // "future" (デフォルト) または "all"
}

レスポンス:
{
  "success": true,
  "message": "固定費を更新しました",
  "data": { ... }
}
```

### エンドポイント4: 固定費削除

```
DELETE /api/fixed-expenses/{id}

レスポンス:
{
  "success": true,
  "message": "固定費を削除しました"
}
```

### エンドポイント5: 支出作成時に固定費フラグ付き

```
POST /api/contents

リクエスト:
{
  "user_id": 1,
  "type_id": 2,
  "category_id": 5,
  "amount": 100000,
  "content": "賃貸マンション",
  "recorded_at": "2026-04-01",
  "is_fixed_expense": true,  ← 新しい フラグ
  "fixed_expense_day": 1     ← 新しい フィールド
}

レスポンス:
{
  "success": true,
  "message": "支出を作成しました",
  "data": { ... }
}
```

---

## 🎨 UI/UX デザイン

### PC版フロー（固定費登録：方法①）

```
1. ユーザーが「新規支出」フォームを開く
   ↓
2. フォームを入力:
   - カテゴリ: 家賃
   - 金額: ¥100,000
   - 内容: 賃貸マンション
   - 「固定費として登録」: チェック
   ↓
3. 「保存」をクリック
   ↓
4. バックエンド処理:
   - contents テーブルに作成
   - fixed_expenses テーブルに定義を作成
   - 毎月自動複製を予約
   ↓
5. 成功メッセージ: 「固定費を作成しました」
   ↓
6. フォームをクリア
```

### PC版フロー（固定費管理：方法②）

```
1. ユーザーが左メニュー「固定費管理」をクリック
   ↓
2. 固定費一覧画面を表示:
   ┌───────────────────────────────────────┐
   │ 固定費管理                 [+ 追加]     │
   │                                       │
   │ □ 家賃       ¥100,000  毎月1日  [編][削]│
   │ □ 保険       ¥5,000    毎月15日 [編][削]│
   │ □ インター   ¥3,000    毎月20日 [編][削]│
   └───────────────────────────────────────┘
   ↓
3a. ユーザーが「[+ 追加]」をクリック
   ↓
   固定費登録フォーム表示
   ↓
   入力 → 「保存」
   ↓
   一覧に追加表示

3b. または「編集」をクリック
   ↓
   編集フォーム表示
   ↓
   金額変更 → 「この日以降に適用」選択 → 「保存」
   ↓
   完了メッセージ

3c. または「削除」をクリック
   ↓
   確認ダイアログ表示
   ↓
   「削除」をクリック
   ↓
   一覧から削除
```

### バッチ処理フロー（毎月1日朝5時自動実行）

```
毎月1日 05:00 JST に実行（日本人が最も使用しない時間帯）
↓
1. fixed_expenses テーブルから全ユーザーの有効な固定費を取得
   ↓
2. チャンク処理（100件単位）で処理開始:
   - fixed_expense_day を読み取り
   - 当月の実行日を計算
     * 1～28日：そのまま使用
     * 29～31日：スマート調整
       - その日が当月に存在 → その日を使用
       - その日が当月に存在しない → 月末日を使用
   - contents テーブルに複製レコード作成
   ↓
3. 各チャンクごとに結果をログ出力
   ↓
4. 全処理完了
   ↓
5. エラーが発生した場合：
   - ログに詳細を記録
   - 管理者へメール/Slack通知
   - リトライ：同日朝6時に再実行
```

---

## 🧪 テスト計画

### 単体テスト（バックエンド）

1. **正常系**
   - 固定費を作成できる ✓
   - 固定費を更新できる ✓
   - 固定費を削除できる ✓
   - 毎月自動複製が実行される ✓
   - 複製時に正しい金額が反映される ✓
   - 金額変更後、未来の複製から新しい金額が適用される ✓
   - 過去の複製は金額変更の影響を受けない ✓

2. **異常系**
   - 無効な固定費の実行日（29～31日）→ エラー ✓
   - 他ユーザーの固定費を操作しようとする → 権限エラー ✓
   - 存在しない固定費を更新/削除 → エラー ✓
   - 無効な category_id → エラー ✓

### 統合テスト（フロントエンド + バックエンド）

1. **UI操作確認**
   - 支出作成フォームで「固定費として登録」チェックボックス表示 ✓
   - 固定費管理画面の一覧表示・編集・削除 ✓
   - 固定費登録フォーム（方法②）の日付入力 ✓
   - 変更時の「この日以降に適用」オプション ✓

2. **データ保存確認**
   - 固定費作成後、fixed_expenses テーブルに記録される ✓
   - 固定費削除後、新しい複製は生成されない ✓
   - 毎月1日に複製が生成される ✓
   - カレンダービューで固定費バッジが表示される ✓

### E2Eテスト（ユーザー視点）

1. 「固定費を登録して、毎月自動で複製される」
2. 「固定費の金額を変更しても、過去の記録は変わらない」
3. 「固定費を削除すると、新しい複製は生成されなくなる」
4. 「カレンダーで固定費バッジが表示される」

---

## 🚀 実装方針

### バックエンド（Laravel）

1. **マイグレーション作成**
   ```bash
   php artisan make:migration create_fixed_expenses_table
   php artisan make:migration add_fixed_expense_columns_to_contents_table
   ```

2. **モデル作成**
   ```bash
   php artisan make:model FixedExpense
   ```

3. **コントローラー作成**
   ```bash
   php artisan make:controller FixedExpenseController --api
   ```

4. **API ルート追加**
   ```php
   // routes/api.php
   Route::apiResource('fixed-expenses', FixedExpenseController::class);
   ```

5. **バッチ処理実装**
   - `app/Console/Commands/ReplicateFixedExpenses.php` コマンド作成
   - `app/Console/Kernel.php` でスケジューリング設定
   ```php
   // 毎月1日 朝5時（JST）に実行
   $schedule->command('fixed-expenses:replicate')
       ->monthlyOn(1, '05:00')
       ->timezone('Asia/Tokyo')
       ->onOneServer();  // 複数サーバー環境での重複実行を防止
   
   // リトライ：失敗時は同日の朝6時に再実行
   $schedule->command('fixed-expenses:replicate')
       ->monthlyOn(1, '06:00')
       ->timezone('Asia/Tokyo')
       ->onOneServer();
   ```
   
   **実装の重要な考慮点**:
   - DB負荷を軽減するため、バッチはチャンク処理（例：100件ずつ）で実行
   - 失敗時は管理者へメール/Slack通知
   - ログ記録で実行結果を追跡可能に

### フロントエンド（React）

1. **固定費管理ページコンポーネント**
   - `src/Components/FixedExpenseManager.tsx`
   - `src/Components/FixedExpenseList.tsx`
   - `src/Components/FixedExpenseForm.tsx`

2. **支出作成フォーム修正**
   - `src/Components/ExpenseForm.tsx` に「固定費として登録」チェックボックス追加

3. **API クライアント**
   - `src/api/fixedExpenseApi.ts` に CRUD メソッド追加

4. **状態管理**
   - React Query または Context API で固定費の状態を管理

---

## 📊 影響範囲

### 修正ファイル

**バックエンド**:
- `app/Http/Controllers/FixedExpenseController.php` - 新規作成
- `app/Models/FixedExpense.php` - 新規作成
- `app/Models/Content.php` - リレーション追加
- `app/Console/Commands/ReplicateFixedExpenses.php` - 新規作成
- `app/Console/Kernel.php` - スケジューリング追加
- `routes/api.php` - ルート追加
- `database/migrations/` - マイグレーションファイル追加

**フロントエンド**:
- `src/Components/FixedExpenseManager.tsx` - 新規作成
- `src/Components/FixedExpenseList.tsx` - 新規作成
- `src/Components/FixedExpenseForm.tsx` - 新規作成
- `src/Components/ExpenseForm.tsx` - チェックボックス追加
- `src/api/fixedExpenseApi.ts` - 新規作成
- `src/app.tsx` - ルート追加（固定費管理ページ）

### 既存機能への影響

- ✅ カレンダービュー表示：バッジ追加のみ（既存機能に影響なし）
- ✅ 支出削除：固定費フラグは削除の判定に使用しない（既存動作と同じ）
- ✅ 支出編集：通常の支出と同じ（固定費は個別編集不可だが、複製された支出は通常の支出として編集可能）

---

## 🎯 完了条件

- [ ] 固定費テーブルが作成され、マイグレーションが成功
- [ ] 固定費の作成・更新・削除 API が動作
- [ ] 毎月自動複製バッチが正常に実行
- [ ] カレンダービューで「固定費」バッジが表示
- [ ] 固定費管理画面（方法②）が実装され、UI が動作
- [ ] 支出作成フォーム（方法①）に「固定費として登録」チェックボックスが追加
- [ ] 単体テスト・統合テストが全てPass
- [ ] E2Eテストで実装漏れがない

---

## 📝 優先度・難易度

| 項目 | 難易度 | 優先度 |
|------|--------|--------|
| DB スキーマ（テーブル作成） | 低 | 高 |
| API（CRUD エンドポイント） | 中 | 高 |
| バッチ処理（毎月自動複製） | 高 | 高 |
| UI（固定費管理画面） | 中 | 中 |
| UI（支出フォーム修正） | 低 | 高 |
| テスト | 中 | 高 |

**推奨実装順序**:
1. DB スキーマ（マイグレーション）
2. API エンドポイント（CRUD）
3. バッチ処理（毎月自動複製）
4. フロントエンド UI（固定費管理画面 + 支出フォーム修正）
5. テスト

---

## 📌 実装上の注意点

### 実行タイミングの考慮

**朝5時（05:00 JST）を選定した理由**:
- 日本人の利用パターン分析
  * 朝4～5時：最も使用しない時間帯
  * 昼9～18時：仕事中で使用者多数（避けるべき）
  * 夜19～23時：帰宅後で使用者多数（避けるべき）
- DB負荷の最小化：バッチ処理による画面重くなりを回避
- ネットワーク負荷の軽減：早朝の低トラフィック時に実行

### バッチ処理の実装工夫

**パフォーマンス最適化**:
```php
// チャンク処理で DB 負荷を分散
FixedExpense::where('is_active', true)
    ->chunk(100, function ($expenses) {
        foreach ($expenses as $expense) {
            // 複製処理
        }
        sleep(1);  // 各チャンク間に1秒のディレイ
    });
```

**エラーハンドリング**:
- リトライ機構：失敗時は同日朝6時に再実行
- 管理者通知：Slack/メール で失敗情報を送信
- ログ記録：storage/logs で詳細を記録
- 手動リトライ：管理画面「今すぐ実行」ボタン

### 実行方式の選定

**推奨**: Laravel Scheduler（サーバー上で実行）

```php
// app/Console/Kernel.php
$schedule->command('fixed-expenses:replicate')
    ->monthlyOn(1, '05:00')
    ->timezone('Asia/Tokyo')
    ->onOneServer();  // 複数サーバー環境での重複実行を防止
```

**理由**:
- 既存インフラで実装可能（追加コストなし）
- セットアップがシンプル
- AWS Lambda より保守性が高い

### 金額変更時の履歴管理

**方針 A**: `fixed_expense_history` テーブルで履歴管理
- 変更時に履歴レコード作成
- 複製時に有効な金額を lookup

**方針 B**: `contents` テーブルの `fixed_expense_id` + `recorded_at` で追跡
- シンプルだが、複製時の金額計算が複雑
- 推奨: 方針 A

---
