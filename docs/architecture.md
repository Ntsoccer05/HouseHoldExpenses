# アーキテクチャ設計書

**プロジェクト**: カケポン（HouseHold Expenses）  
**バージョン**: 1.0  
**最終更新**: 2026-04-13

---

## 目次

1. [テクノロジースタック](#テクノロジースタック)
2. [アーキテクチャパターン](#アーキテクチャパターン)
3. [バックエンドレイヤー設計](#バックエンドレイヤー設計)
4. [ディレクトリ構造](#ディレクトリ構造)
5. [設計パターン](#設計パターン)
6. [データベース設計](#データベース設計)
7. [セキュリティアーキテクチャ](#セキュリティアーキテクチャ)
8. [パフォーマンス考慮事項](#パフォーマンス考慮事項)
9. [スケーラビリティ設計](#スケーラビリティ設計)

---

## テクノロジースタック

### バックエンド（API層）

| 技術 | バージョン | 用途 | 選定理由 |
|------|-----------|------|---------|
| **PHP** | 8.2+ | サーバーサイド言語 | Laravel標準環境。型安全性と実行速度のバランスが良い |
| **Laravel** | 10.x | Webフレームワーク | Eloquent ORM、Sanctum認証、豊富なミドルウェア、日本語リソースが充実 |
| **MySQL** | 8.0+ | データベース | ACID準拠、トランザクション対応、複合インデックス対応 |
| **Laravel Sanctum** | 3.x | API認証 | SPA用トークン認証、セキュアで実装が簡単 |
| **Filament** | 3.x | 管理パネル | Laravel統合、プラグイン豊富、保守性高い |

### フロントエンド（UI層）

| 技術 | バージョン | 用途 | 選定理由 |
|------|-----------|------|---------|
| **React** | 18.x | UIフレームワーク | コンポーネント志向、豊富なライブラリエコシステーム、学習曲線が穏やか |
| **TypeScript** | 5.x | 言語 | 静的型付け、IDE補完強力、バグ検出が容易 |
| **Vite** | 5.x | ビルドツール | 高速なホットリロード、esbuild ベースで本番ビルドも高速 |
| **Material-UI** | 5.x | UIコンポーネントライブラリ | デザインシステム完備、レスポンシブ対応、アクセシビリティ配慮 |

### 開発ツール

| 技術 | バージョン | 用途 | 選定理由 |
|------|-----------|------|---------|
| **Docker** | 24.x | コンテナ化 | 開発環境の統一、本番環境への移行が容易 |
| **Composer** | 2.x | PHPパッケージ管理 | Laravel標準、依存関係管理が厳密 |
| **npm** | 10.x | JavaScriptパッケージ管理 | Node.js標準、モダンなパッケージ運用 |
| **PHPUnit** | 10.x | テストフレームワーク | Laravel標準、機能テスト対応 |
| **Laravel Pint** | 1.x | PHPコードフォーマッター | Laravel標準、PSR-12準拠 |

---

## アーキテクチャパターン

### システム全体の構成

```
┌─────────────────────────────────────────────────────────────┐
│                    クライアント層                              │
│  ┌──────────────────┐        ┌──────────────────┐           │
│  │  React SPA       │        │  Filament Admin  │           │
│  │  (ユーザー向け)   │        │  (管理者向け)     │           │
│  └────────┬─────────┘        └────────┬─────────┘           │
└───────────┼──────────────────────────┼─────────────────────┘
            │                          │
         HTTP/REST                  HTTP/REST
            │                          │
┌───────────┼──────────────────────────┼─────────────────────┐
│   API     │                          │                      │
│  層       ▼                          ▼                      │
│  ┌──────────────────────────────────────────┐              │
│  │     Laravel Router + Middleware          │              │
│  │     (認証・CORS・レート制限など)          │              │
│  └────────┬─────────────────────────────────┘              │
│           │                                                 │
│  ┌────────┼──────────────────────────────────┐             │
│  │ Controller層                              │             │
│  │ (リクエスト/レスポンス処理のみ)            │             │
│  └────────┼──────────────────────────────────┘             │
│           │                                                 │
│  ┌────────┼──────────────────────────────────┐             │
│  │ Service層                                 │             │
│  │ (ビジネスロジック・トランザクション管理)    │             │
│  │ ┌──────────────────────────────────────┐ │             │
│  │ │ FixedExpenseService                  │ │             │
│  │ │ TransactionService                   │ │             │
│  │ │ CategoryService                      │ │             │
│  │ └──────────────────────────────────────┘ │             │
│  └────────┼──────────────────────────────────┘             │
│           │                                                 │
│  ┌────────┼──────────────────────────────────┐             │
│  │ Repository層                              │             │
│  │ (データベース操作の抽象化)                 │             │
│  └────────┼──────────────────────────────────┘             │
│           │                                                 │
│  ┌────────┼──────────────────────────────────┐             │
│  │ Model層                                   │             │
│  │ (Eloquent ORM・リレーション・検証)        │             │
│  │ ┌──────────────────────────────────────┐ │             │
│  │ │ User, Content, Category, Type        │ │             │
│  │ │ FixedExpense, FixedExpenseHistory    │ │             │
│  │ └──────────────────────────────────────┘ │             │
│  └────────┼──────────────────────────────────┘             │
│           │                                                 │
└───────────┼─────────────────────────────────────────────────┘
            │
         MySQL
            │
    ┌───────▼──────┐
    │  データベース  │
    └──────────────┘
```

---

## バックエンドレイヤー設計

### 1. Controller層（プレゼンテーション層）

**責務**:
- HTTPリクエストの受け取り
- リクエストのバリデーション（FormRequest経由）
- Serviceの呼び出し
- レスポンスのフォーマットと返却

**特徴**:
- ビジネスロジックを含まない（薄いController）
- エラーハンドリングは最小限（例外処理）
- 全てのレスポンスは ResponseFormatter を経由

**実装例**:
```php
// ✅ Good: Controller は薄い
public function store(StoreFixedExpenseRequest $request)
{
    try {
        $fixedExpense = $this->service->createFixedExpense(
            $request->validated(),
            auth()->id()
        );
        return response()->json(
            ResponseFormatter::success($fixedExpense),
            201
        );
    } catch (\Exception $e) {
        return response()->json(
            ResponseFormatter::error($e->getMessage(), 'ERROR'),
            500
        );
    }
}

// ❌ Bad: ビジネスロジックが Controller に混在
public function store(Request $request)
{
    $data = $request->validate([...]);
    $fixedExpense = FixedExpense::create($data);  // DB操作
    // ビジネスロジック...
    return response()->json([...]);
}
```

### 2. Service層（ビジネスロジック層）

**責務**:
- ビジネスロジックの実装
- トランザクション管理
- 複数のRepositoryを組み合わせた処理
- ドメイン例外の発生
- データ変換・加工

**特徴**:
- Interfaceを実装して依存性注入される
- トランザクション（DB::transaction）をここで管理
- Utilsクラスを使用
- テスト容易（モック化可能）

**実装例**:
```php
class FixedExpenseService implements FixedExpenseServiceInterface
{
    public function __construct(
        private FixedExpenseRepositoryInterface $repository,
        private DateCalculator $dateCalculator,
    ) {}
    
    public function createFixedExpense(array $data, int $userId): FixedExpense
    {
        // バリデーション
        if (!$this->dateCalculator->isValidDay($data['fixed_expense_day'])) {
            throw new FixedExpenseException('Invalid day');
        }
        
        // トランザクション処理
        return DB::transaction(function () use ($data, $userId) {
            $data['user_id'] = $userId;
            return $this->repository->create($data);
        });
    }
}
```

### 3. Repository層（データアクセス層）

**責務**:
- データベース操作の実装（CRUD）
- クエリの構築と実行
- データベースの詳細を隠ぺい

**特徴**:
- Interfaceを実装
- Modelに依存（逆は依存しない）
- 複雑なクエリはここに集約
- テスト容易（モック化可能）

**実装例**:
```php
class FixedExpenseRepository implements FixedExpenseRepositoryInterface
{
    public function getActiveForReplication(): Collection
    {
        return FixedExpense::where('is_active', true)
            ->with(['category', 'user'])
            ->chunk(100, function ($expenses) {
                // チャンク処理
            });
    }
}
```

### 4. Model層（ドメイン層）

**責務**:
- データベーステーブルへのマッピング
- リレーション定義
- スコープの定義
- キャストの定義

**特徴**:
- ビジネスロジックを含まない
- Traitで共通機能を提供
- ファクトリで テストデータを生成
- Enumを使用した型安全性

**実装例**:
```php
class FixedExpense extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'category_id', 'amount', ...];
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

### 5. FormRequest層（リクエスト検証層）

**責務**:
- リクエストデータのバリデーション
- 認可チェック
- データの正規化

**特徴**:
- authorize() でユーザー権限を確認
- rules() でバリデーションルールを定義
- カスタムメッセージで日本語対応

**実装例**:
```php
class StoreFixedExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }
    
    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|integer|min:1',
            'fixed_expense_day' => 'required|integer|between:1,31',
        ];
    }
    
    public function messages(): array
    {
        return [
            'fixed_expense_day.between' => '実行日は1～31日を指定してください',
        ];
    }
}
```

---

## ディレクトリ構造

```
src/
├── app/
│   ├── Console/
│   │   ├── Commands/
│   │   │   └── ReplicateFixedExpenses.php     (定期実行バッチ)
│   │   └── Kernel.php                         (スケジューラー設定)
│   │
│   ├── Enums/
│   │   ├── TypeEnum.php                       (収入/支出の種別)
│   │   ├── FixedExpenseStatusEnum.php         (固定費の状態)
│   │   └── ErrorCodeEnum.php                  (エラーコード定義)
│   │
│   ├── Exceptions/
│   │   ├── APIBusinessLogicException.php      (API例外基底)
│   │   └── FixedExpenseException.php          (固定費関連例外)
│   │
│   ├── Interfaces/
│   │   ├── Repositories/
│   │   │   ├── FixedExpenseRepositoryInterface.php
│   │   │   └── TransactionRepositoryInterface.php
│   │   └── Services/
│   │       ├── FixedExpenseServiceInterface.php
│   │       └── TransactionServiceInterface.php
│   │
│   ├── Repositories/
│   │   ├── FixedExpenseRepository.php
│   │   └── TransactionRepository.php
│   │
│   ├── Services/
│   │   ├── FixedExpenseService.php            (ビジネスロジック)
│   │   ├── TransactionService.php
│   │   └── Strategies/
│   │       └── FixedExpenseReplicationStrategy.php
│   │
│   ├── Traits/
│   │   ├── ValidatesWithRules.php             (バリデーション再利用)
│   │   ├── FormatsDates.php                   (日付フォーマッティング)
│   │   └── HasAuditLog.php                    (監査ログ記録)
│   │
│   ├── Utils/
│   │   ├── DateCalculator.php                 (日付計算: 29-31日対応)
│   │   ├── ResponseFormatter.php              (レスポンス統一)
│   │   └── ValidationHelper.php               (バリデーション補助)
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── FixedExpenseController.php
│   │   │   ├── TransactionController.php
│   │   │   └── CategoryController.php
│   │   │
│   │   ├── Requests/
│   │   │   ├── StoreFixedExpenseRequest.php
│   │   │   ├── UpdateFixedExpenseRequest.php
│   │   │   ├── StoreTransactionRequest.php
│   │   │   └── CopyMultipleContentsRequest.php
│   │   │
│   │   ├── Resources/
│   │   │   ├── FixedExpenseResource.php       (API レスポンス)
│   │   │   └── TransactionResource.php
│   │   │
│   │   └── Middleware/
│   │       └── (認証、CORS等)
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Content.php
│   │   ├── FixedExpense.php
│   │   ├── FixedExpenseHistory.php            (金額変更履歴)
│   │   ├── Category.php
│   │   └── Type.php
│   │
│   └── Filament/
│       └── Resources/
│           ├── FixedExpenseResource.php       (管理画面)
│           └── ContentResource.php
│
├── database/
│   ├── migrations/
│   │   ├── 2024_06_15_004734_create_contents_table.php
│   │   ├── 2024_xx_xx_xxxxxx_create_fixed_expenses_table.php
│   │   └── 2024_xx_xx_xxxxxx_add_fixed_expense_columns_to_contents_table.php
│   │
│   ├── factories/
│   │   └── FixedExpenseFactory.php
│   │
│   └── seeders/
│       └── FixedExpenseSeeder.php
│
├── routes/
│   ├── api.php                                (API ルート定義)
│   └── web.php                                (Filament 管理画面ルート)
│
├── tests/
│   ├── Feature/
│   │   ├── FixedExpenseTest.php
│   │   └── CopyMultipleContentsTest.php
│   └── Unit/
│       ├── Services/FixedExpenseServiceTest.php
│       └── Utils/DateCalculatorTest.php
│
└── config/
    ├── app.php
    ├── database.php
    └── (その他 Laravel設定)
```

---

## 設計パターン

### 1. Enum パターン

**用途**: ビジネス上の定義値を型安全で管理

```php
// app/Enums/ErrorCodeEnum.php
enum ErrorCodeEnum: string
{
    case SAME_DATE_ERROR = 'SAME_DATE_ERROR';
    case INVALID_DAY = 'INVALID_FIXED_EXPENSE_DAY';
    
    public function message(): string { ... }
    public function httpStatus(): int { ... }
}

// 使用例
throw new FixedExpenseException(
    ErrorCodeEnum::INVALID_DAY->message(),
    ErrorCodeEnum::INVALID_DAY->value
);
```

### 2. Repository パターン

**用途**: データベース操作の抽象化

```php
// Interface で契約を定義
interface FixedExpenseRepositoryInterface
{
    public function create(array $data): FixedExpense;
    public function getActiveForReplication(): Collection;
}

// 実装
class FixedExpenseRepository implements FixedExpenseRepositoryInterface { ... }

// Service で注入・使用
class FixedExpenseService
{
    public function __construct(
        private FixedExpenseRepositoryInterface $repository
    ) {}
}
```

### 3. Service パターン

**用途**: ビジネスロジックの集約

```php
class FixedExpenseService implements FixedExpenseServiceInterface
{
    // トランザクション管理、複数Repository の組み合わせ
    public function createFixedExpense(array $data): FixedExpense
    {
        return DB::transaction(function () use ($data) {
            // 複数の操作を原子的に実行
        });
    }
}
```

### 4. Trait パターン

**用途**: 共通機能の再利用

```php
// 日付フォーマッティングの共通化
trait FormatsDates
{
    protected function formatDate(\DateTime $date): string
    {
        return $date->format('Y-m-d');
    }
}

// Model で使用
class FixedExpense extends Model
{
    use FormatsDates;
}
```

### 5. Strategy パターン

**用途**: アルゴリズムの切り替え

```php
// 固定費複製戦略
interface ReplicationStrategy
{
    public function replicate(FixedExpense $expense): Content;
}

class MonthlyReplicationStrategy implements ReplicationStrategy
{
    public function replicate(FixedExpense $expense): Content { ... }
}
```

### 6. Utils パターン

**用途**: 純粋関数として提供される汎用機能

```php
class DateCalculator
{
    // ビジネスロジックではない、再利用可能な計算
    public function calculateExecutionDate(
        int $year,
        int $month,
        int $day
    ): \DateTime { ... }
    
    public function isValidDay(int $day): bool
    {
        return $day >= 1 && $day <= 31;
    }
}
```

---

## データベース設計

### テーブル構成

#### fixed_expenses テーブル
```sql
CREATE TABLE fixed_expenses (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    type_id INT NOT NULL DEFAULT 2,          -- 常に「支出」
    category_id BIGINT NOT NULL,
    amount INT NOT NULL,                      -- 基本金額
    content VARCHAR(255) NOT NULL,
    fixed_expense_day INT NOT NULL,           -- 1～31日（スマート調整される）
    is_active BOOLEAN DEFAULT true,
    last_replicated_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,                -- ソフトデリート
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    INDEX idx_last_replicated (last_replicated_at)
);
```

#### fixed_expense_history テーブル（オプション）
```sql
CREATE TABLE fixed_expense_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    fixed_expense_id BIGINT NOT NULL,
    amount INT NOT NULL,
    effective_from DATE NOT NULL,             -- この金額がいつから有効か
    created_at TIMESTAMP NOT NULL,
    
    FOREIGN KEY (fixed_expense_id) REFERENCES fixed_expenses(id) ON DELETE CASCADE,
    INDEX idx_fixed_expense_id (fixed_expense_id),
    INDEX idx_effective_from (effective_from)
);
```

#### contents テーブル（既存テーブルへの追加カラム）
```sql
ALTER TABLE contents ADD COLUMN (
    is_fixed_expense BOOLEAN DEFAULT false,
    fixed_expense_day INT NULL,
    fixed_expense_id BIGINT NULL,
    
    FOREIGN KEY (fixed_expense_id) REFERENCES fixed_expenses(id) ON DELETE SET NULL,
    INDEX idx_fixed_expense (is_fixed_expense),
    INDEX idx_fixed_expense_id (fixed_expense_id)
);
```

### インデックス戦略

| テーブル | カラム | 目的 | 効果 |
|---------|--------|------|------|
| fixed_expenses | (user_id, is_active) | ユーザーの有効な固定費を取得 | 複合インデックスで高速化 |
| fixed_expenses | last_replicated_at | 未複製の固定費を検出 | バッチ処理の効率化 |
| contents | fixed_expense_id | 複製元を追跡 | 監査ログ用途 |

---

## セキュリティアーキテクチャ

### 認証・認可

| レイヤー | 対策 | 実装 |
|---------|------|------|
| **API認証** | Sanctum トークン認証 | `->guard('sanctum')` |
| **リクエスト認可** | FormRequest の authorize() | ユーザーIDチェック |
| **データアクセス認可** | Policyパターン | `$user->can('update', $fixedExpense)` |
| **監査ログ** | HasAuditLog Trait | 変更履歴を自動記録 |

### データ保護

| 対策 | 実装 |
|-----|------|
| **SQLインジェクション対策** | Eloquent ORM + Prepared Statements |
| **CSRF対策** | Laravel CSRF token |
| **CORS** | .env で CLIENT_URL 設定 |
| **レート制限** | throttle ミドルウェア |

### 入力検証

```php
// FormRequest で多層検証
class StoreFixedExpenseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|integer|min:1|max:9999999',
            'fixed_expense_day' => 'required|integer|between:1,31',
        ];
    }
    
    public function authorize(): bool
    {
        // ユーザー権限確認
        return auth()->check();
    }
}
```

---

## パフォーマンス考慮事項

### バッチ処理の最適化

#### 固定費複製バッチ（毎月1日 05:00 JST）

**実行タイミング**: 朝5時（日本人が最も使用しない時間帯）
- 9～18時（業務時間）: ピーク時間帯を避ける
- 19～23時（帰宅後）: ユーザー利用が多い

**実装方法**:
```php
// app/Console/Kernel.php
$schedule->command('fixed-expenses:replicate')
    ->monthlyOn(1, '05:00')
    ->timezone('Asia/Tokyo')
    ->onOneServer();  // 複数サーバー環境での重複実行防止

// リトライ: 失敗時は朝6時に再実行
$schedule->command('fixed-expenses:replicate')
    ->monthlyOn(1, '06:00')
    ->timezone('Asia/Tokyo')
    ->onOneServer();
```

**チャンク処理による DB 負荷分散**:
```php
FixedExpense::where('is_active', true)
    ->chunk(100, function ($expenses) {
        foreach ($expenses as $expense) {
            // 複製処理
        }
        sleep(1);  // 各チャンク間に1秒のディレイ
    });
```

### クエリ最適化

| パターン | 対策 |
|---------|------|
| N+1 問題 | `with()` による Eager Loading |
| 大量データ | `chunk()` による分割処理 |
| インデックス | 複合インデックス活用 |

---

## スケーラビリティ設計

### 水平スケーリング対応

| 機能 | 実装 |
|------|------|
| **セッション管理** | Redis キャッシュ |
| **ジョブキュー** | Laravel Queue（バッチ処理非同期化） |
| **キャッシュ** | Redis/APCu |
| **定期実行** | `onOneServer()` で重複実行防止 |

### 将来の拡張性

- **Microservices対応**: API層とBusiness層の分離で容易に移行可能
- **キューシステム**: 負荷が高い場合、バッチをジョブキューに変更可能
- **キャッシング**: Service層で Cacheファサード活用
- **GraphQL対応**: Controller層の変更で対応可能（Service層は影響なし）

---

## テスト戦略

### テスト階層

```
E2E テスト (Integration)
    ↑
ユニットテスト (Unit)
    ↑
コード品質検証 (Lint/Type)
```

| レベル | ツール | 対象 | 実行タイミング |
|--------|--------|------|-------------|
| **Unit** | PHPUnit | Service・Utils・Model | コミット前 |
| **Feature** | PHPUnit | Controller・FormRequest | CI/CD |
| **Type** | phpstan | 全PHP | コミット前 |
| **Lint** | Pint | コード形式 | コミット前 |

### テストカバレッジ目標

| レイヤー | 目標カバレッジ |
|---------|------------|
| Service | 80% 以上 |
| Utility | 90% 以上 |
| Model | 70% 以上 |
| Controller | 60% 以上 |
| 全体 | 75% 以上 |

---

## 技術的制約

### 環境要件

| 項目 | 要件 |
|------|------|
| **PHP** | 8.2 以上 |
| **MySQL** | 8.0 以上 |
| **Node.js** | 18.x 以上 |
| **メモリ** | 最低 512MB（開発環境）、2GB（本番環境） |
| **ディスク** | 最低 10GB |

### パフォーマンス制約

- **固定費複製処理**: 5000件以上の場合、チャンク処理により CPU使用率 30% 以下に抑える
- **API レスポンスタイム**: 200ms 以内（平均）
- **ページロード時間**: 2秒以内

---

## 依存関係管理

### 主要ライブラリのバージョン管理方針

| ライブラリ | バージョン管理 | 理由 |
|----------|------------|------|
| Laravel | 固定（^10.x） | LTS版、5年サポート |
| PHP | 固定（^8.2） | セキュリティパッチ重視 |
| Eloquent | Laravel依存 | フレームワーク統合 |
| React | 範囲指定（^18.0） | メジャー変更時の検証 |
| Material-UI | 範囲指定（^5.x） | マイナー更新で互換性保証 |

### 非推奨・廃止予定の依存関係

- jQuery: React に移行完了
- Bootstrap: Material-UI に統一

---

## まとめ

このアーキテクチャは以下の目標を達成しています：

✅ **責務の分離**: Controller（薄い）→ Service → Repository → Model
✅ **テスト容易性**: 各層が独立してテスト可能
✅ **保守性**: コードの意図が明確、新機能追加時の作業量が予測可能
✅ **スケーラビリティ**: 複数サーバー対応、負荷分散対応
✅ **セキュリティ**: 多層防御、入力検証、認可チェック
✅ **パフォーマンス**: バッチ処理最適化、インデックス戦略、キャッシング対応

---

**版履歴**

| 版 | 日付 | 変更内容 |
|----|------|---------|
| 1.0 | 2026-04-13 | 初版作成。Laravel 10 バックエンド設計を確立 |
