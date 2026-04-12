# DB設計書更新テンプレート

## テーブル定義テンプレート

```markdown
### テーブル: reports

**説明**: ユーザーの報告書情報を管理するテーブル

| カラム名 | データ型 | 制約 | 説明 |
|---------|--------|------|------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | 報告書ID |
| user_id | BIGINT | NOT NULL, FOREIGN KEY | ユーザーID (users テーブルを参照) |
| title | VARCHAR(255) | NOT NULL | 報告書タイトル |
| description | TEXT | NULLABLE | 報告書説明 |
| report_type | ENUM('monthly', 'yearly', 'custom') | NOT NULL, DEFAULT='monthly' | 報告書タイプ |
| status | ENUM('draft', 'published', 'archived') | NOT NULL, DEFAULT='draft' | ステータス |
| published_at | TIMESTAMP | NULLABLE | 公開日時 |
| created_at | TIMESTAMP | NOT NULL, DEFAULT=CURRENT_TIMESTAMP | 作成日時 |
| updated_at | TIMESTAMP | NOT NULL, DEFAULT=CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新日時 |
| deleted_at | TIMESTAMP | NULLABLE | ソフトデリート日時 |

**キー**:
- PRIMARY KEY: id
- FOREIGN KEY: user_id → users(id) ON DELETE CASCADE
- UNIQUE: (user_id, title) - ユーザーごとのタイトルユニーク

**インデックス**:
- INDEX idx_user_id: user_id
- INDEX idx_status: status
- INDEX idx_created_at: created_at DESC
- COMPOSITE INDEX idx_user_status: (user_id, status)
```

## カラム追加テンプレート

```markdown
### 既存テーブル: transactions への追加

| カラム名 | データ型 | 制約 | 説明 |
|---------|--------|------|------|
| report_id | BIGINT | NULLABLE, FOREIGN KEY | レポートID (reports テーブルを参照) |

**リレーション**:
- FOREIGN KEY: report_id → reports(id) ON DELETE SET NULL
```

## ER図更新テンプレート

```markdown
## ER図

```
┌─────────────┐
│    users    │
├─────────────┤
│ id (PK)     │
│ name        │
│ email (UQ)  │
│ created_at  │
└──────┬──────┘
       │ 1
       │
       │ *
┌──────┴──────────┬──────────────────┐
│                 │                  │
│                 │                  │
v                 v                  v
┌─────────────┐  ┌──────────────┐  ┌──────────┐
│transactions │  │  categories  │  │  reports │
├─────────────┤  ├──────────────┤  ├──────────┤
│ id (PK)     │  │ id (PK)      │  │ id (PK)  │
│ user_id (FK)├──│ user_id (FK) │  │ user_id  │
│ category_id ├──│              │  │ title    │
│ amount      │  │ content      │  │ status   │
│ recorded_at │  │ icon         │  │ type     │
└─────────────┘  └──────────────┘  └──────────┘
```
```

## リレーション定義テンプレート

```markdown
## リレーション定義

| リレーション | 親テーブル | 子テーブル | 親カラム | 子カラム | DELETE動作 |
|----------|----------|----------|---------|---------|----------|
| User → Report | users | reports | id | user_id | CASCADE |
| Report → Transaction | reports | transactions | id | report_id | SET NULL |
| User → Category | users | categories | id | user_id | CASCADE |
| Category → Transaction | categories | transactions | id | category_id | CASCADE |
```

## インデックス戦略テンプレート

```markdown
## インデックス戦略

### パフォーマンス最適化インデックス

| テーブル | インデックス名 | カラム | 用途 |
|--------|----------|-------|------|
| transactions | idx_user_recorded | (user_id, recorded_at DESC) | ユーザーの取引一覧高速化 |
| transactions | idx_category_user | (category_id, user_id) | カテゴリ別取引集計 |
| reports | idx_user_status | (user_id, status) | ユーザーの報告書フィルタ |
| categories | idx_user_content | (user_id, content) | カテゴリ検索高速化 |

### ユニークインデックス

| テーブル | インデックス名 | カラム | 説明 |
|--------|----------|-------|------|
| users | uq_email | email | メールアドレス重複防止 |
| reports | uq_user_title | (user_id, title) | ユーザー内でのタイトル一意性 |
```

## マイグレーション反映テンプレート

```markdown
## 最近のマイグレーション

**日付**: 2026-04-12
**ファイル**: `2026_04_12_xxxxxx_create_reports_table.php`

**実装内容**:
- reports テーブル作成
- report_items テーブル作成
- transactions.report_id カラム追加
- インデックス5個追加

**ER図への影響**:
- 新テーブル: reports, report_items
- 新リレーション: User → Report, Report → Transaction
```
