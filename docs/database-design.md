# HouseHold Expenses - データベース設計書

**バージョン**: 1.1.0  
**最終更新**: 2026-07-13  
**責任者**: Development Team

---

## 目次

1. [概要](#概要)
2. [ER図](#er図)
3. [テーブル仕様](#テーブル仕様)
4. [リレーション一覧](#リレーション一覧)
5. [インデックス戦略](#インデックス戦略)
6. [データベース管理](#データベース管理)

---

## 概要

**データベース**: MySQL 8.0 以上  
**字集合**: utf8mb4（絵文字対応）  
**照合順序**: utf8mb4_unicode_ci  
**接続先**: `DB_HOST=db_household`, `DB_DATABASE=householdExpensesApp`

### 主な特徴

- ユーザーごとの独立したデータ管理（マルチテナント対応）
- ソフトデリート対応（deleted フラグで論理削除）
- オーディット対応（created_at, updated_at で変更履歴追跡）
- 外部キー制約で データ整合性を保証

---

## ER図

```
┌─────────────┐         ┌────────────┐
│   users     │◄─┐  ┌──►│   types    │
└──────┬──────┘  │  │   └────────────┘
       │         │  │
   ┌───┴─────────┘  │
   │          ┌──────┴────────────┐
   │          │                   │
┌──┴────────┐┌┴───────────────┐ ┌─┴─────────────────┐
│ contents  ││income_categories││expence_categories │
│(取引)     │└──────────────────┘ └───────────────────┘
└──┬────────┘
   │ n:1（fixed_expense_id、nullable）
┌──┴───────────────┐
│ fixed_expenses    │
│ (固定費)          │
└───────────────────┘

┌─────────────┐
│   users     │
└──────┬──────┘
       │ 1:n
┌──────┴──────────┐
│  split_groups   │
│ (分担グループ)   │
└──┬───────────┬──┘
   │ 1:1        │ 1:n
┌──┴─────────────────────┐  ┌─┴──────────────────────────────┐
│ split_group_settings   │  │ split_group_category_overrides │
│ (按分設定)              │  │ (カテゴリ別按分の上書き)         │
└─────────────────────────┘  └──────────────────────────────────┘
```

---

## テーブル仕様

### 1. users（ユーザー）

**説明**: アプリケーションのユーザー情報  
**レコード数**: 数千～数百万（ユーザー数に依存）

| カラム名 | 型 | NULL | デフォルト | キー | 説明 |
|---------|-----|------|----------|------|------|
| id | BIGINT UNSIGNED | NO | | PRIMARY | ユーザーID（主キー） |
| name | VARCHAR(255) | NO | | | ユーザー名 |
| email | VARCHAR(255) | NO | | UNIQUE | メールアドレス（ユニーク） |
| email_verified_at | TIMESTAMP | YES | NULL | | メール認証日時 |
| password | VARCHAR(255) | YES | NULL | | パスワードハッシュ |
| remember_token | VARCHAR(100) | YES | NULL | | トークン保存用 |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 作成日時 |
| updated_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 更新日時 |

**インデックス**:
- PRIMARY KEY (id)
- UNIQUE KEY (email)
- KEY (created_at)

**用途**:
- ユーザー認証（ログイン・パスワード）
- メール検証
- ユーザー情報管理

---

### 2. types（タイプ）

**説明**: 取引の種類（収入/支出）の定義  
**レコード数**: 少数（マスタデータ）

| カラム名 | 型 | NULL | デフォルト | キー | 説明 |
|---------|-----|------|----------|------|------|
| id | BIGINT UNSIGNED | NO | | PRIMARY | タイプID |
| name | VARCHAR(255) | YES | NULL | | タイプ名（日本語） |
| en_name | VARCHAR(255) | YES | NULL | | タイプ名（英語） |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 作成日時 |
| updated_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 更新日時 |

**データ例**:
```
1 | 収入 | Income
2 | 支出 | Expense
```

**用途**:
- 取引タイプの分類
- 収入カテゴリ・支出カテゴリの区分

---

### 3. income_categories（収入カテゴリ）

**説明**: ユーザーの収入カテゴリ定義  
**レコード数**: ユーザーあたり 5～20 件

| カラム名 | 型 | NULL | デフォルト | キー | 説明 |
|---------|-----|------|----------|------|------|
| id | BIGINT UNSIGNED | NO | | PRIMARY | カテゴリID |
| user_id | BIGINT UNSIGNED | NO | | FK | ユーザーID（users テーブル） |
| type_id | BIGINT UNSIGNED | NO | | FK | タイプID（types テーブル） |
| content | VARCHAR(255) | NO | 0 | | カテゴリ名（例：給料） |
| icon | VARCHAR(255) | NO | | | アイコン（絵文字など） |
| filtered_id | INT | YES | NULL | | フィルタID（予約） |
| deleted | INT | NO | 0 | | ソフトデリートフラグ（0:有効, 1:削除） |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 作成日時 |
| updated_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 更新日時 |

**インデックス**:
- PRIMARY KEY (id)
- FOREIGN KEY (user_id) REFERENCES users(id)
- FOREIGN KEY (type_id) REFERENCES types(id)
- KEY (user_id, deleted) ← ユーザー別・削除状態で検索

**用途**:
- ユーザーの収入カテゴリ管理（給料、ボーナス、その他収入など）
- 取引時のカテゴリ選択

**備考**: 
- `content` がカテゴリ名（例：給料）
- `icon` で絵文字を保存（例：💰）
- `deleted=1` で論理削除

---

### 4. expence_categories（支出カテゴリ）

**説明**: ユーザーの支出カテゴリ定義  
**レコード数**: ユーザーあたり 10～50 件

| カラム名 | 型 | NULL | デフォルト | キー | 説明 |
|---------|-----|------|----------|------|------|
| id | BIGINT UNSIGNED | NO | | PRIMARY | カテゴリID |
| user_id | BIGINT UNSIGNED | NO | | FK | ユーザーID（users テーブル） |
| type_id | BIGINT UNSIGNED | NO | | FK | タイプID（types テーブル） |
| content | VARCHAR(255) | NO | | | カテゴリ名（例：食費） |
| icon | VARCHAR(255) | NO | | | アイコン（絵文字など） |
| filtered_id | INT | YES | NULL | | フィルタID（予約） |
| deleted | INT | NO | 0 | | ソフトデリートフラグ（0:有効, 1:削除） |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 作成日時 |
| updated_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 更新日時 |

**インデックス**:
- PRIMARY KEY (id)
- FOREIGN KEY (user_id) REFERENCES users(id)
- FOREIGN KEY (type_id) REFERENCES types(id)
- KEY (user_id, deleted)

**用途**:
- ユーザーの支出カテゴリ管理（食費、交通費、医療費など）
- 取引時のカテゴリ選択

**備考**:
- income_categories と構造は同じ
- type_id は常に 2（支出）

---

### 5. contents（取引記録）

**説明**: ユーザーの収入・支出記録  
**レコード数**: ユーザーあたり 数百～数千件

| カラム名 | 型 | NULL | デフォルト | キー | 説明 |
|---------|-----|------|----------|------|------|
| id | BIGINT UNSIGNED | NO | | PRIMARY | 取引ID |
| user_id | BIGINT UNSIGNED | NO | | FK | ユーザーID（users テーブル） |
| type_id | BIGINT UNSIGNED | NO | | FK | タイプID（types テーブル：1=収入, 2=支出） |
| category_id | BIGINT UNSIGNED | NO | | FK | カテゴリID（income_categories or expence_categories） |
| amount | INT | NO | | | 金額（円） |
| content | VARCHAR(255) | YES | NULL | | 取引内容メモ（例：スーパー、給料 など） |
| is_fixed_expense | BOOLEAN | NO | false | INDEX | 固定費から生成された取引かどうか |
| fixed_expense_day | TINYINT UNSIGNED | YES | NULL | | 固定費の実行日（1〜31、is_fixed_expense=true の場合のみ使用） |
| fixed_expense_id | BIGINT UNSIGNED | YES | NULL | INDEX | 生成元の固定費ID（fixed_expenses テーブル、外部キー制約なし） |
| recorded_at | DATETIME | NO | | INDEX | 取引日時 |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 作成日時 |
| updated_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 更新日時 |

**インデックス**:
- PRIMARY KEY (id)
- FOREIGN KEY (user_id) REFERENCES users(id)
- FOREIGN KEY (type_id) REFERENCES types(id)
- FOREIGN KEY (category_id) REFERENCES [income_categories|expence_categories](id)
- KEY idx_user_recorded_at (user_id, recorded_at) ← ユーザー別・日時で検索（重要）
- KEY (user_id, type_id, recorded_at) ← タイプ別検索最適化
- KEY idx_contents_is_fixed_expense (is_fixed_expense)
- KEY idx_contents_fixed_expense_id (fixed_expense_id)

**用途**:
- 日々の取引記録（入出金）
- 月別・年別集計
- カテゴリ別分析
- 固定費バッチ（`fixed_expenses` テーブル）から複製された取引の追跡

**パフォーマンス考慮**:
- `recorded_at` でインデックス → 月別・日別検索が高速
- `user_id + recorded_at` 複合インデックス → ユーザーの月別データ取得が高速

**金額の型**:
- `INT` で最大 21 億円に対応（通常の家計管理に十分）
- より大きな金額が必要な場合は `BIGINT` に変更

**備考**:
- `monthly_amounts` テーブル（月別集計キャッシュ）は廃止済み（`2026_05_26_143711_drop_monthly_amounts_table.php`）。月別集計は `contents` から都度算出する方式に変更されている

---

### 6. fixed_expenses（固定費）

**説明**: ユーザーが登録した固定収支（家賃・サブスクなど）の定義。バッチ処理で毎月 `contents` に複製される  
**レコード数**: ユーザー × タイプ（収入/支出）あたり最大10件（アプリ側で制限）

| カラム名 | 型 | NULL | デフォルト | キー | 説明 |
|---------|-----|------|----------|------|------|
| id | BIGINT UNSIGNED | NO | | PRIMARY | 固定費ID |
| user_id | BIGINT UNSIGNED | NO | | FK | ユーザーID（users テーブル） |
| type_id | TINYINT UNSIGNED | NO | 2 | | タイプID（1=収入, 2=支出） |
| category_id | BIGINT UNSIGNED | NO | | | カテゴリID（income_categories or expence_categories、外部キー制約なし） |
| amount | INT UNSIGNED | NO | | | 金額（円） |
| content | VARCHAR(255) | NO | | | 内容メモ（例：家賃） |
| fixed_expense_day | TINYINT UNSIGNED | NO | | | 毎月の実行日（1〜31。月末日を超える場合は月末に丸める） |
| is_active | BOOLEAN | NO | true | INDEX | 有効フラグ（false でバッチ複製対象外） |
| last_replicated_at | TIMESTAMP | YES | NULL | | バッチ処理で `contents` へ最後に複製した日時 |
| deactivated_at | TIMESTAMP | YES | NULL | | 無効化した日時（is_active が false になった時点で自動設定） |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 作成日時 |
| updated_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 更新日時 |

**インデックス**:
- PRIMARY KEY (id)
- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
- KEY (is_active)

**用途**:
- 固定費（家賃、サブスクなど）の定義管理
- 定期実行バッチ（`ReplicateFixedExpenses`）が毎月 `contents` へ複製し、`last_replicated_at` を更新
- 複製済みの取引は `contents.fixed_expense_id` で紐づく

**備考**:
- 当初は `deleted_at`（ソフトデリート）を持っていたが `2026_05_26_144033_update_fixed_expenses_remove_soft_deletes_add_deactivated_at.php` で廃止し、`deactivated_at` による無効化管理に変更された

---

### 7. split_groups（分担グループ）

**説明**: 世帯・グループ単位で支出を割り勘（按分）するためのグループ定義  
**レコード数**: ユーザーあたり数件

| カラム名 | 型 | NULL | デフォルト | キー | 説明 |
|---------|-----|------|----------|------|------|
| id | BIGINT UNSIGNED | NO | | PRIMARY | 分担グループID |
| user_id | BIGINT UNSIGNED | NO | | FK | ユーザーID（users テーブル） |
| label | VARCHAR(100) | NO | | | グループ表示名（例：家族A） |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 作成日時 |
| updated_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 更新日時 |

**インデックス**:
- PRIMARY KEY (id)
- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
- KEY (user_id)

**用途**:
- 収入・支出の按分（自分負担 / 相手負担）設定の単位

**備考**:
- `is_active` カラムは一時的に存在したが `2026_05_27_000000_drop_is_active_from_split_groups_table.php` で削除済み

---

### 8. split_group_settings（分担グループ按分設定）

**説明**: 分担グループごとの按分比率・固定調整額（1グループにつき1レコード）  
**レコード数**: split_groups と同数

| カラム名 | 型 | NULL | デフォルト | キー | 説明 |
|---------|-----|------|----------|------|------|
| id | BIGINT UNSIGNED | NO | | PRIMARY | ID |
| split_group_id | BIGINT UNSIGNED | NO | | FK, UNIQUE | 分担グループID（split_groups テーブル） |
| income_other_ratio | TINYINT UNSIGNED | YES | NULL | | 収入のうち相手負担とする割合（%）。未設定なら按分対象外 |
| income_other_offset | INT | YES | NULL | | 収入按分後に加減算する固定額 |
| expense_other_ratio | TINYINT UNSIGNED | YES | NULL | | 支出のうち相手負担とする割合（%）。未設定なら按分対象外 |
| expense_other_offset | INT | YES | NULL | | 支出按分後に加減算する固定額 |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 作成日時 |
| updated_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 更新日時 |

**インデックス**:
- PRIMARY KEY (id)
- FOREIGN KEY (split_group_id) REFERENCES split_groups(id) ON DELETE CASCADE
- UNIQUE KEY (split_group_id)

**用途**:
- 分担グループ作成時に自動的に1レコード作成（ratio は NULL）
- プレビュー計算（`/split-groups/{id}/preview`）の基準値

---

### 9. split_group_category_overrides（カテゴリ別按分の上書き）

**説明**: 特定カテゴリのみ按分比率を個別に上書きする設定  
**レコード数**: グループごとに0〜カテゴリ数分

| カラム名 | 型 | NULL | デフォルト | キー | 説明 |
|---------|-----|------|----------|------|------|
| id | BIGINT UNSIGNED | NO | | PRIMARY | ID |
| split_group_id | BIGINT UNSIGNED | NO | | FK | 分担グループID（split_groups テーブル） |
| category_id | BIGINT UNSIGNED | NO | | | カテゴリID（income_categories or expence_categories、外部キー制約なし） |
| type_id | TINYINT UNSIGNED | NO | | | タイプID（1=収入, 2=支出） |
| other_ratio | TINYINT UNSIGNED | NO | | | このカテゴリにおける相手負担割合（%） |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 作成日時 |
| updated_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 更新日時 |

**インデックス**:
- PRIMARY KEY (id)
- FOREIGN KEY (split_group_id) REFERENCES split_groups(id) ON DELETE CASCADE
- UNIQUE KEY split_group_category_unique (split_group_id, category_id, type_id)

**用途**:
- 「食費だけは70%相手負担」のようなカテゴリ単位の例外設定
- 設定更新時は既存レコードを全削除してから一括再作成（全置換方式）

---

### 10. password_resets（パスワードリセット）

**説明**: パスワードリセットトークン  
**レコード数**: 少数（アクティブなトークン）

| カラム名 | 型 | NULL | デフォルト | キー | 説明 |
|---------|-----|------|----------|------|------|
| email | VARCHAR(255) | NO | | PRIMARY | メールアドレス |
| token | VARCHAR(255) | NO | | INDEX | リセットトークン |
| created_at | TIMESTAMP | YES | NULL | | 作成日時 |

**用途**:
- パスワードリセットメール内のトークン保管
- 一定期間（デフォルト60分）で自動削除

---

### 11. password_reset_tokens（パスワードリセットトークン）

**説明**: パスワードリセット用トークン（新形式）  
**レコード数**: 少数

| カラム名 | 型 | NULL | デフォルト | キー | 説明 |
|---------|-----|------|----------|------|------|
| email | VARCHAR(255) | NO | | PRIMARY | メールアドレス |
| token | VARCHAR(255) | NO | | | リセットトークン |
| created_at | TIMESTAMP | YES | NULL | | 作成日時 |

**備考**: `password_resets` より新しい形式

---

### 12. failed_jobs（失敗したジョブ）

**説明**: 非同期ジョブの失敗ログ  
**レコード数**: 少数（エラーの場合のみ記録）

| カラム名 | 型 | NULL | デフォルト | キー | 説明 |
|---------|-----|------|----------|------|------|
| id | BIGINT UNSIGNED | NO | | PRIMARY | ID |
| uuid | VARCHAR(255) | NO | | UNIQUE | ジョブUUID |
| connection | TEXT | NO | | | 接続名 |
| queue | TEXT | NO | | | キュー名 |
| payload | LONGTEXT | NO | | | ジョブペイロード（JSON） |
| exception | LONGTEXT | NO | | | エラーメッセージ |
| failed_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 失敗日時 |

**用途**:
- メール送信失敗時のログ
- ジョブ再試行の参考情報

---

### 13. admin_users（管理者ユーザー）

**説明**: Filament 管理画面の管理者ユーザー  
**レコード数**: 少数（管理者のみ）

| カラム名 | 型 | NULL | デフォルト | キー | 説明 |
|---------|-----|------|----------|------|------|
| id | BIGINT UNSIGNED | NO | | PRIMARY | ID |
| name | VARCHAR(255) | NO | | | 管理者名 |
| email | VARCHAR(255) | NO | | UNIQUE | 管理者メール |
| password | VARCHAR(255) | NO | | | パスワードハッシュ |
| email_verified_at | TIMESTAMP | YES | NULL | | 認証日時 |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 作成日時 |
| updated_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 更新日時 |

**用途**:
- Filament 管理画面へのアクセス制御
- ユーザー・カテゴリ・コンテンツの管理

---

## リレーション一覧

### 親テーブル → 子テーブル

| 親テーブル | カラム | 子テーブル | カラム | 種類 | アクション |
|----------|-------|----------|-------|------|---------|
| users | id | income_categories | user_id | 1:n | CASCADE |
| users | id | expence_categories | user_id | 1:n | CASCADE |
| users | id | contents | user_id | 1:n | CASCADE |
| users | id | fixed_expenses | user_id | 1:n | CASCADE |
| users | id | split_groups | user_id | 1:n | CASCADE |
| types | id | income_categories | type_id | 1:n | CASCADE |
| types | id | expence_categories | type_id | 1:n | CASCADE |
| types | id | contents | type_id | 1:n | CASCADE |
| income_categories | id | contents | category_id | 1:n | CASCADE |
| expence_categories | id | contents | category_id | 1:n | CASCADE |
| fixed_expenses | id | contents | fixed_expense_id | 1:n | なし（外部キー制約なし。アプリ側で紐付け管理） |
| split_groups | id | split_group_settings | split_group_id | 1:1 | CASCADE |
| split_groups | id | split_group_category_overrides | split_group_id | 1:n | CASCADE |

### 削除時の動作

**ON DELETE CASCADE**:
- ユーザー削除時 → そのユーザーのカテゴリ・取引・固定費・分担グループも削除
- カテゴリ削除時 → そのカテゴリの取引も削除
- 分担グループ削除時 → その按分設定・カテゴリ別上書き設定も削除

⚠️ **注意**: データ削除時に意図しない大量削除を防ぐため、実装時はソフトデリート（論理削除）も検討すること。

---

## インデックス戦略

### 検索パターン別インデックス

| 検索パターン | インデックス | 理由 |
|----------|----------|------|
| ユーザーのカテゴリ一覧 | (user_id, deleted) | ユーザー別・削除状態でフィルタ |
| ユーザーの月別取引 | (user_id, recorded_at) | ユーザー別・日付でフィルタ |
| ユーザーの年別集計 | (user_id, recorded_at) | 年月抽出可能 |
| カテゴリ別支出集計 | (category_id, recorded_at) | カテゴリ別・期間で集計 |
| 月別レポート生成 | (user_id, type_id, recorded_at) | ユーザー・タイプ・日付で集計 |
| 固定費由来の取引抽出 | contents(is_fixed_expense) / contents(fixed_expense_id) | バッチ複製済みかどうかの判定・固定費単位での紐付け取得 |
| 有効な固定費一覧 | fixed_expenses(is_active) | バッチ処理対象（is_active=true）の絞り込み |
| ユーザーの分担グループ一覧 | split_groups(user_id) | ユーザー別のグループ取得 |
| カテゴリ別按分上書きの一意性 | split_group_category_overrides(split_group_id, category_id, type_id) | 同一グループ内でのカテゴリ×タイプの重複防止 |

### パフォーマンス最適化

```sql
-- 高速化のための複合インデックス例
CREATE INDEX idx_user_recorded_at ON contents(user_id, recorded_at);
CREATE INDEX idx_category_date ON contents(category_id, recorded_at DESC);
CREATE INDEX idx_user_type_date ON contents(user_id, type_id, recorded_at DESC);
CREATE INDEX idx_contents_is_fixed_expense ON contents(is_fixed_expense);
CREATE INDEX idx_contents_fixed_expense_id ON contents(fixed_expense_id);
```

---

## データベース管理

### バックアップ戦略

**頻度**: 日1回以上（本番環境）

```bash
# MySQL ダンプ
mysqldump -u root -p householdExpensesApp > backup_$(date +%Y%m%d).sql

# Docker の場合
docker-compose exec db mysqldump -u root -p householdExpensesApp > backup.sql
```

### マイグレーション管理

```bash
# 新しいマイグレーションファイル作成
php artisan make:migration create_new_table

# マイグレーション実行
php artisan migrate

# ロールバック
php artisan migrate:rollback
```

### パフォーマンス監視

```sql
-- インデックスの使用状況確認
SELECT * FROM sys.schema_unused_indexes;

-- スロークエリログ確認
SELECT * FROM mysql.slow_log;

-- テーブル統計情報
SELECT * FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'householdExpensesApp';
```

### ユーザーデータ削除時の注意

**オプション1: ハードデリート（データ完全削除）**
```sql
DELETE FROM users WHERE id = 1;
-- CASCADE により関連レコードも自動削除
```

**オプション2: ソフトデリート（論理削除）**
```sql
-- users テーブルに deleted_at カラムを追加
ALTER TABLE users ADD COLUMN deleted_at TIMESTAMP NULL;

-- 削除フラグを立てる
UPDATE users SET deleted_at = NOW() WHERE id = 1;

-- クエリ実行時に論理削除されたユーザーを除外
SELECT * FROM users WHERE deleted_at IS NULL;
```

---

## 付録：SQL スキーマ定義

### テーブル作成スクリプト

```sql
-- 文字セット・照合順序設定
SET CHARACTER SET utf8mb4;
SET COLLATION_CONNECTION = utf8mb4_unicode_ci;

-- users テーブル
CREATE TABLE users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  email_verified_at TIMESTAMP NULL,
  password VARCHAR(255),
  remember_token VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_email (email),
  KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- types テーブル
CREATE TABLE types (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255),
  en_name VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- income_categories テーブル
CREATE TABLE income_categories (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  type_id BIGINT UNSIGNED NOT NULL,
  content VARCHAR(255) NOT NULL,
  icon VARCHAR(255) DEFAULT '',
  filtered_id INT,
  deleted INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (type_id) REFERENCES types(id) ON DELETE CASCADE,
  KEY idx_user_deleted (user_id, deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- expence_categories テーブル（支出）
CREATE TABLE expence_categories (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  type_id BIGINT UNSIGNED NOT NULL,
  content VARCHAR(255) NOT NULL,
  icon VARCHAR(255) DEFAULT '',
  filtered_id INT,
  deleted INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (type_id) REFERENCES types(id) ON DELETE CASCADE,
  KEY idx_user_deleted (user_id, deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- contents テーブル（取引記録）
CREATE TABLE contents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  type_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  amount INT NOT NULL,
  content VARCHAR(255),
  is_fixed_expense BOOLEAN NOT NULL DEFAULT FALSE,
  fixed_expense_day TINYINT UNSIGNED NULL,
  fixed_expense_id BIGINT UNSIGNED NULL,
  recorded_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (type_id) REFERENCES types(id) ON DELETE CASCADE,
  KEY idx_user_recorded_at (user_id, recorded_at),
  KEY idx_user_type_date (user_id, type_id, recorded_at DESC),
  KEY idx_contents_is_fixed_expense (is_fixed_expense),
  KEY idx_contents_fixed_expense_id (fixed_expense_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- fixed_expenses テーブル（固定費）
CREATE TABLE fixed_expenses (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  type_id TINYINT UNSIGNED NOT NULL DEFAULT 2,
  category_id BIGINT UNSIGNED NOT NULL,
  amount INT UNSIGNED NOT NULL,
  content VARCHAR(255) NOT NULL,
  fixed_expense_day TINYINT UNSIGNED NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  last_replicated_at TIMESTAMP NULL,
  deactivated_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  KEY idx_fixed_expenses_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- split_groups テーブル（分担グループ）
CREATE TABLE split_groups (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  label VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  KEY idx_split_groups_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- split_group_settings テーブル（分担グループ按分設定）
CREATE TABLE split_group_settings (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  split_group_id BIGINT UNSIGNED NOT NULL,
  income_other_ratio TINYINT UNSIGNED NULL,
  income_other_offset INT NULL,
  expense_other_ratio TINYINT UNSIGNED NULL,
  expense_other_offset INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (split_group_id) REFERENCES split_groups(id) ON DELETE CASCADE,
  UNIQUE KEY unique_split_group (split_group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- split_group_category_overrides テーブル（カテゴリ別按分の上書き）
CREATE TABLE split_group_category_overrides (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  split_group_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  type_id TINYINT UNSIGNED NOT NULL,
  other_ratio TINYINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (split_group_id) REFERENCES split_groups(id) ON DELETE CASCADE,
  UNIQUE KEY split_group_category_unique (split_group_id, category_id, type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 今後の拡張計画

### 予算管理機能（将来）
```sql
-- budgets テーブル（予算）
CREATE TABLE budgets (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED,
  limit_amount DECIMAL(10, 2) NOT NULL,
  alert_threshold INT DEFAULT 80,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### タグ機能（将来）
```sql
-- tags テーブル
CREATE TABLE tags (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- content_tag テーブル（中間テーブル）
CREATE TABLE content_tag (
  content_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (content_id, tag_id),
  FOREIGN KEY (content_id) REFERENCES contents(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 最近のマイグレーション履歴

### 2026-05-26 〜 2026-05-27（固定費管理・分担グループ機能）

**ファイル**:
- `2026_05_26_002019_create_fixed_expenses_table.php`
- `2026_05_26_002029_add_fixed_expense_columns_to_contents_table.php`
- `2026_05_26_143711_drop_monthly_amounts_table.php`
- `2026_05_26_144033_update_fixed_expenses_remove_soft_deletes_add_deactivated_at.php`
- `2026_05_26_161440_create_split_groups_table.php`
- `2026_05_26_161450_create_split_group_settings_table.php`
- `2026_05_26_161452_create_split_group_category_overrides_table.php`
- `2026_05_26_171000_add_offset_to_split_group_settings_table.php`
- `2026_05_27_000000_drop_is_active_from_split_groups_table.php`

**内容**:
- `fixed_expenses` テーブル作成（固定費管理機能）
- `contents` に `is_fixed_expense` / `fixed_expense_day` / `fixed_expense_id` カラム追加
- `monthly_amounts` テーブル廃止（月別集計は `contents` から都度算出する方式に変更）
- `fixed_expenses` のソフトデリートを廃止し `deactivated_at` による無効化管理に変更
- `split_groups` / `split_group_settings` / `split_group_category_overrides` テーブル作成（分担グループ・支出の割り勘機能）
- `split_group_settings` に固定調整額カラム（`income_other_offset` / `expense_other_offset`）追加
- `split_groups` の `is_active` カラムを削除

**テーブル数**: 10個 → 13個

### 2026-04-14

**ファイル**: `2026_04_14_000000_add_composite_index_to_contents_table.php`

**内容**:
- `contents(user_id, recorded_at)` の複合インデックス `idx_user_recorded_at` を追加（月次トランザクション取得の高速化）

---

## 参考資料

- [Laravel Database Migrations](https://laravel.com/docs/migrations)
- [MySQL インデックス最適化](https://dev.mysql.com/doc/)
- [PostgreSQL vs MySQL パフォーマンス比較](https://www.postgresql.org/)
