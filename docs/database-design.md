# HouseHold Expenses - データベース設計書

**バージョン**: 1.0.0  
**最終更新**: 2026-04-12  
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
└─────────────┘  │  │   └────────────┘
       ▲         │  │
       │         │  │
   ┌───┘         │  │
   │          ┌──┴──┴─────────────┐
   │          │                   │
┌──┴────────┐┌┴───────────────┐ ┌─┴─────────────────┐
│ contents  ││income_categories││expence_categories │
│(取引)     │└──────────────────┘ └───────────────────┘
└───────────┘
   │
   │ 1:n
┌──┴──────────────┐
│monthly_amounts  │
│(月別集計)       │
└─────────────────┘
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
| recorded_at | DATETIME | NO | | INDEX | 取引日時 |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 作成日時 |
| updated_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 更新日時 |

**インデックス**:
- PRIMARY KEY (id)
- FOREIGN KEY (user_id) REFERENCES users(id)
- FOREIGN KEY (type_id) REFERENCES types(id)
- FOREIGN KEY (category_id) REFERENCES [income_categories|expence_categories](id)
- KEY (user_id, recorded_at) ← ユーザー別・日時で検索（重要）
- KEY (user_id, type_id, recorded_at) ← タイプ別検索最適化

**用途**:
- 日々の取引記録（入出金）
- 月別・年別集計
- カテゴリ別分析

**パフォーマンス考慮**:
- `recorded_at` でインデックス → 月別・日別検索が高速
- `user_id + recorded_at` 複合インデックス → ユーザーの月別データ取得が高速

**金額の型**:
- `INT` で最大 21 億円に対応（通常の家計管理に十分）
- より大きな金額が必要な場合は `BIGINT` に変更

---

### 6. monthly_amounts（月別集計）

**説明**: ユーザーの月別収支集計（キャッシュ）  
**レコード数**: ユーザー × 月数

| カラム名 | 型 | NULL | デフォルト | キー | 説明 |
|---------|-----|------|----------|------|------|
| id | BIGINT UNSIGNED | NO | | PRIMARY | ID |
| user_id | BIGINT UNSIGNED | NO | | FK | ユーザーID（users テーブル） |
| amount | INT | NO | | | 月別金額（集計値） |
| recorded_at | DATETIME | NO | | INDEX | 月の開始日（例：2026-04-01） |
| created_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 作成日時 |
| updated_at | TIMESTAMP | YES | CURRENT_TIMESTAMP | | 更新日時 |

**インデックス**:
- PRIMARY KEY (id)
- FOREIGN KEY (user_id) REFERENCES users(id)
- UNIQUE KEY (user_id, recorded_at) ← ユーザー・月の組み合わせはユニーク

**用途**:
- 月別の収支集計値をキャッシュ
- 画面表示時に `contents` テーブルから集計し直すのではなく、このテーブルを読む（高速化）

**更新タイミング**:
- 取引が追加・更新・削除されたときに、該当月の `monthly_amounts` も更新

**備考**:
- `amount` の意味は文脈により異なる（収入合計、支出合計、収支差など）
- 実装時に `income_amount`, `expense_amount` に分割することも検討

---

### 7. password_resets（パスワードリセット）

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

### 8. password_reset_tokens（パスワードリセットトークン）

**説明**: パスワードリセット用トークン（新形式）  
**レコード数**: 少数

| カラム名 | 型 | NULL | デフォルト | キー | 説明 |
|---------|-----|------|----------|------|------|
| email | VARCHAR(255) | NO | | PRIMARY | メールアドレス |
| token | VARCHAR(255) | NO | | | リセットトークン |
| created_at | TIMESTAMP | YES | NULL | | 作成日時 |

**備考**: `password_resets` より新しい形式

---

### 9. failed_jobs（失敗したジョブ）

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

### 10. admin_users（管理者ユーザー）

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
| users | id | monthly_amounts | user_id | 1:n | CASCADE |
| types | id | income_categories | type_id | 1:n | CASCADE |
| types | id | expence_categories | type_id | 1:n | CASCADE |
| types | id | contents | type_id | 1:n | CASCADE |
| income_categories | id | contents | category_id | 1:n | CASCADE |
| expence_categories | id | contents | category_id | 1:n | CASCADE |

### 削除時の動作

**ON DELETE CASCADE**:
- ユーザー削除時 → そのユーザーのカテゴリ・取引も削除
- カテゴリ削除時 → そのカテゴリの取引も削除

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

### パフォーマンス最適化

```sql
-- 高速化のための複合インデックス例
CREATE INDEX idx_user_date ON contents(user_id, recorded_at DESC);
CREATE INDEX idx_category_date ON contents(category_id, recorded_at DESC);
CREATE INDEX idx_user_type_date ON contents(user_id, type_id, recorded_at DESC);
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
  recorded_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (type_id) REFERENCES types(id) ON DELETE CASCADE,
  KEY idx_user_date (user_id, recorded_at DESC),
  KEY idx_user_type_date (user_id, type_id, recorded_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- monthly_amounts テーブル（月別集計）
CREATE TABLE monthly_amounts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  amount INT NOT NULL,
  recorded_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_month (user_id, recorded_at)
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

## 参考資料

- [Laravel Database Migrations](https://laravel.com/docs/migrations)
- [MySQL インデックス最適化](https://dev.mysql.com/doc/)
- [PostgreSQL vs MySQL パフォーマンス比較](https://www.postgresql.org/)
