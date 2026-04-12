# DB設計書更新ガイド

## 1. マイグレーション内容の確認

実装したマイグレーションファイルを確認して、以下を把握します：

### 確認項目

```bash
# マイグレーションファイルを確認
ls -la src/database/migrations/ | grep 2026_
cat src/database/migrations/2026_04_12_xxxxxx_*.php
```

### マイグレーションから抽出する情報

1. **テーブル作成**
   - テーブル名
   - 各カラムの定義（型、制約）
   - インデックス

2. **既存テーブルへのカラム追加**
   - テーブル名
   - カラム名、型、制約
   - デフォルト値

3. **外部キー制約**
   - 親テーブル、親カラム
   - 子テーブル、子カラム
   - DELETE時の動作（CASCADE, SET NULL など）

## 2. テーブル定義追加手順

### Step 1: マイグレーション確認

```php
// src/database/migrations/2026_04_12_xxxxxx_create_reports_table.php

Schema::create('reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->string('title');
    $table->text('description')->nullable();
    $table->enum('status', ['draft', 'published'])->default('draft');
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    // インデックス
    $table->index('user_id');
    $table->index('status');
    $table->unique(['user_id', 'title']);
});
```

### Step 2: DB設計書に追加

`docs/database-design.md` のテーブル一覧セクションに追加：

```markdown
### テーブル: reports

**説明**: ユーザーの報告書情報を管理

| カラム名 | データ型 | 制約 | 説明 |
|---------|--------|------|------|
| id | BIGINT | PRIMARY KEY | 報告書ID |
| user_id | BIGINT | NOT NULL, FOREIGN KEY | ユーザーID |
| title | VARCHAR(255) | NOT NULL | タイトル |
| status | ENUM | NOT NULL, DEFAULT='draft' | ステータス |
| published_at | TIMESTAMP | NULLABLE | 公開日時 |
| created_at | TIMESTAMP | NOT NULL | 作成日時 |
| updated_at | TIMESTAMP | NOT NULL | 更新日時 |
| deleted_at | TIMESTAMP | NULLABLE | ソフトデリート |

**リレーション**:
- FOREIGN KEY: user_id → users(id) ON DELETE CASCADE
- UNIQUE: (user_id, title)

**インデックス**:
- INDEX idx_user_id: user_id
- INDEX idx_status: status
- UNIQUE idx_user_title: (user_id, title)
```

## 3. 既存テーブルへのカラム追加

### Step 1: マイグレーション確認

```php
// src/database/migrations/2026_04_12_xxxxxx_add_report_id_to_transactions.php

Schema::table('transactions', function (Blueprint $table) {
    $table->foreignId('report_id')
        ->nullable()
        ->constrained('reports')
        ->onDelete('set null');
});
```

### Step 2: DB設計書に更新

既存テーブルのカラム定義に新規カラムを追加：

```markdown
### テーブル: transactions

| カラム名 | データ型 | 制約 | 説明 |
|---------|--------|------|------|
| ... 既存カラム ... |
| report_id | BIGINT | NULLABLE, FOREIGN KEY | レポートID (新規追加) |
```

## 4. リレーション定義の更新

マイグレーション内の `foreignId()` から以下を把握：

### 外部キーの種類

| DELETE動作 | 用途 | 例 |
|-----------|------|-----|
| CASCADE | 親削除時に子も削除 | User → Report（ユーザー削除時にレポートも削除） |
| SET NULL | 親削除時に子のFKをNULLに | Report → Transaction（レポート削除時に取引は残す） |
| RESTRICT | 削除を防止 | 使用頻度低い |

### リレーション定義表を更新

```markdown
## リレーション定義

| リレーション | 親テーブル | 子テーブル | 親カラム | 子カラム | DELETE動作 |
|----------|----------|----------|---------|---------|----------|
| User → Report | users | reports | id | user_id | CASCADE |
| Report → Transaction | reports | transactions | id | report_id | SET NULL |
```

## 5. インデックス戦略の更新

### ステップ1: マイグレーションのインデックス確認

```php
$table->index('user_id');           // 単一インデックス
$table->unique(['user_id', 'title']); // ユニークインデックス
$table->index(['user_id', 'status']); // 複合インデックス
```

### ステップ2: インデックス戦略表に追加

```markdown
## インデックス戦略

| テーブル | インデックス名 | カラム | 用途 |
|--------|----------|-------|------|
| reports | idx_user_id | user_id | ユーザーのレポート一覧取得 |
| reports | idx_status | status | ステータスフィルタ |
| reports | uq_user_title | (user_id, title) | ユニーク制約 |
```

## 6. ER図の更新

### ステップ1: 新テーブル追加時

テーブル関係図を更新。新テーブルを追加し、リレーションを描画：

```markdown
```
┌─────────────┐
│    users    │
├─────────────┤
│ id (PK)     │
└──────┬──────┘
       │ 1
       │
       │ *
┌──────┴──────────┐
│                 │
v                 v
┌──────────────┐  ┌──────────┐
│ transactions │  │  reports │ ← 新テーブル
├──────────────┤  ├──────────┤
│ id (PK)      │  │ id (PK)  │
│ user_id (FK) │  │ user_id  │
│ report_id←───┼──│ (FK)     │
│ amount       │  │ title    │
│ recorded_at  │  └──────────┘
└──────────────┘
```
```

### ステップ2: 新しいリレーション追加時

図上の矢印を追加して関係を表現

## 7. マイグレーション履歴の記録

DB設計書の最後に、最近のマイグレーション履歴を記録：

```markdown
## 最近のマイグレーション履歴

### 2026-04-12
**ファイル**: `2026_04_12_xxxxxx_create_reports_table.php`
**内容**:
- reports テーブル作成（32カラム）
- transactions に report_id 追加
- インデックス5個追加
- リレーション3個追加

**テーブル数**: 10個 → 11個

### 2026-04-10
**ファイル**: `2026_04_10_xxxxxx_add_budget_to_categories.php`
**内容**:
- categories に budget カラム追加
- インデックス1個追加
```

## 8. ファイル更新チェックリスト

DB設計書更新時、以下を確認：

### テーブル定義セクション
- [ ] テーブル名、説明が正確
- [ ] すべてのカラムが列挙されている
- [ ] データ型が正しい（INT, BIGINT, VARCHAR など）
- [ ] 制約が正しい（NOT NULL, UNIQUE, PRIMARY KEY）
- [ ] 外部キー制約が記載されている
- [ ] デフォルト値が記載されている

### リレーション定義セクション
- [ ] すべてのFOREIGN KEY が記載
- [ ] DELETE動作が正しい
- [ ] リレーション図と一致している

### インデックス戦略セクション
- [ ] すべてのインデックスが記載
- [ ] インデックス名が一貫している
- [ ] 用途が明確

### ER図
- [ ] 新テーブル/カラムが反映されている
- [ ] リレーション矢印が正しい方向
- [ ] 主キー、外部キーが明記されている

## 9. マイグレーション内容が不確実な場合

マイグレーションファイルから情報が読み取り難い場合：

```bash
# データベースから直接確認
DESCRIBE reports;  # テーブル構造確認
SHOW INDEX FROM reports;  # インデックス確認
SHOW CREATE TABLE reports;  # CREATE文確認（制約詳細）
```

## 10. Laravelマイグレーションの一般的なパターン

```php
// テーブル作成
Schema::create('table_name', function (Blueprint $table) {
    $table->id();  # 自動採番ID
    $table->string('name');  # VARCHAR(255)
    $table->text('description');  # TEXT
    $table->integer('count');  # INT
    $table->decimal('price', 8, 2);  # DECIMAL(8,2)
    $table->boolean('is_active');  # TINYINT(1)
    $table->timestamp('published_at')->nullable();  # TIMESTAMP
    $table->timestamps();  # created_at, updated_at
    $table->softDeletes();  # deleted_at
    $table->foreignId('user_id')->constrained();  # 外部キー
    $table->index('column_name');  # インデックス
});

// カラム追加
Schema::table('table_name', function (Blueprint $table) {
    $table->string('new_column')->after('existing_column');
});

// カラム削除
Schema::table('table_name', function (Blueprint $table) {
    $table->dropColumn('column_name');
});
```
