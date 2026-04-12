# CLAUDE.md

このファイルは、このリポジトリのコードを操作する際に Claude Code (claude.ai/code) へのガイダンスを提供します。

## プロジェクト概要

**HouseHold Expenses (カケポン)** は、以下の要素を持つフルスタック家計管理アプリケーションです：
- **バックエンド**: Laravel 10 と Filament 管理パネル（アカウント・コンテンツ管理用）
- **フロントエンド**: React 18 + TypeScript と Vite（カレンダービュー、支出管理、チャート機能）
- **データベース**: MySQL
- **認証**: Laravel Sanctum (API) + Socialite (OAuth連携)

## クイックスタートコマンド

### インストール
```bash
# ルートから PHP と Node の依存関係をインストール
cd src
composer install
npm install
cp .env.example .env  # 必要に応じて DB とメール認証情報を更新
php artisan key:generate
php artisan migrate
php artisan db:seed  # Seeder が存在する場合
cd ..
```

### 開発
```bash
# ターミナル 1: Docker コンテナを起動（MySQL、Nginx、Mailhog）
docker-compose up -d

# ターミナル 2: src/ から Laravel を実行（Vite のホットリロードに対応）
cd src
php artisan serve --port=9000

# ターミナル 3: src/ から React Vite dev サーバーを実行
cd src
npm run dev  # http://localhost:5173 で実行
```

Laravel バックエンドは `http://localhost:9000` で、React フロントエンドは `http://localhost:5173` で動作します。`.env` ファイルで `CLIENT_URL=http://localhost:5173` に設定されているため、CORS はローカル開発用に設定されています。

### ビルド
```bash
# src/ から React アセットをプロダクション用にビルド
npm run build
# Laravel は自動的にコンパイル済みアセットを提供します
```

### テスト
```bash
# Unit と Feature テストは PHPUnit を使用
cd src
php artisan test  # すべてのテストを実行（Unit + Feature スイート）
php artisan test tests/Unit  # Unit テストのみ実行
php artisan test tests/Unit/SomeTest.php  # 特定のテストを実行
```

### コード品質
```bash
# Laravel Pint（PHP リンター・フィッサー）
cd src
./vendor/bin/pint  # スタイル違反をチェック
./vendor/bin/pint --fix  # スタイル問題を自動修正
```

**Pre-commit フック**（Husky 経由）は `npm run typecheck` と `npx lint-staged` を実行します。TypeScript チェックはコミット時に強制されます。

## アーキテクチャ

### バックエンド構造 (`src/app/`)

- **Http/Controllers/**: 支出管理、カテゴリ、予算、レポートの API エンドポイント
- **Http/Requests/**: フォームリクエスト検証クラス
- **Models/**: Eloquent モデル（User、Expense、Category、Budget など）
- **Filament/Resources/**: コンテンツと設定を管理する管理パネル CRUD インターフェース
- **Http/Services/**: ビジネスロジックサービス（計算、レポート生成）
- **Exceptions/**: API エラー処理用のカスタム例外 `APIBusinessLogicException`
- **Enums/**: `TypeEnum` その他の列挙クラス

### フロントエンド構造 (`src/resources/js/`)

- **app.tsx**: ルーティング設定を含むメインの React エントリーポイント
- **bootstrap.ts**: Laravel バックエンドへの API 呼び出し用 Axios 設定
- **Components/** (予定): UI 用 React コンポーネント
- **Pages/** (予定): 異なるルート用のページレベルコンポーネント

主な依存関係：
- **React Router DOM**: クライアント側のルーティング
- **Material-UI (@mui)**: UI コンポーネントライブラリ
- **React Hook Form + Zod**: フォーム処理と検証
- **FullCalendar + date-fns**: カレンダーと日付ユーティリティ
- **Chart.js**: データ可視化
- **dnd-kit**: ドラッグ＆ドロップ（ソート可能なリスト/支出）
- **FontAwesome**: アイコン

### データベース

- **接続**: MySQL（`.env` に `DB_HOST=db_household` として設定）
- **マイグレーション**: `src/database/migrations/` に配置
- **シーダー**: `src/database/seeders/` に配置（テストデータ用）

## 主要な設定ファイル

- **src/.env**: データベース、メール（ポート 1025 の Mailhog）、Sanctum ドメイン、Filament 認証情報
- **src/config/**: Laravel 設定（app、auth、database、mail など）
- **docker-compose.yml**: マルチサービス設定（PHP アプリ、Nginx Web、MySQL、Mailhog、PhpMyAdmin）
- **Dockerfile**（docker/ 内）: カスタム PHP + 必要な拡張機能

## 開発上の注意

- **API エラーハンドリング**: コントローラーで `APIBusinessLogicException` を使用します。これは JSON レスポンスとしてキャッチされてフォーマットされます
- **Sanctum ステートフルドメイン**: `localhost:5173`（Vite dev サーバー）が認証付きリクエストを行えるように設定されています
- **メールテスト**: Mailhog は `http://localhost:8025` で実行され、開発中のテストメール確認に使用できます
- **管理パネル**: Filament 管理パネルは `/admin` で利用可能です（Filament 認証が必要）
- **ホットモジュールリプレースメント**: 両方の開発サーバーが実行されている場合、Vite のホットモジュールリプレースメント（HMR）は自動的に動作します

## 開発ワークフロー全体図

このプロジェクトは、以下の段階的なプロセスで機能開発を進めます：

### フェーズ0: プロジェクト初期化（初回のみ）

```bash
# 初回セットアップコマンドを実行
/setup-project
```

**実行内容**:
対話的に以下の6つの永続ドキュメントを作成します（既に作成済みの場合はスキップ）：
- `docs/product-requirements.md` - ユーザー要件・成功指標
- `docs/functional-design.md` - 機能仕様・データモデル
- `docs/architecture.md` - システム構成・責任分離
- `docs/repository-structure.md` - ディレクトリ構造・ファイル編成
- `docs/development-guidelines.md` - コーディング規約・開発プロセス
- `docs/glossary.md` - 用語集・定義

**参考**: `.claude/commands/setup-project.md`

---

### フェーズ1: アイデア設計（壁打ち）

```bash
# ユーザーとの対話でアイデアを詰める
# → docs/ideas/pending/ に設計書として記録
```

**流れ**:
1. 新機能 or 改善案について、ユーザーと対話で要件を明確化
2. `docs/ideas/pending/feature-xxx.md` に以下を記載：
   - 概要・背景・動機
   - 機能要件・非機能要件
   - 設計方針・実装方針
   - テスト計画・影響範囲
3. 実装準備完了後、ステップ2へ進む

**テンプレート・ベストプラクティス**: `docs/ideas/README.md` を参照

**例**:
```markdown
docs/ideas/pending/feature-monthly-report.md
- 概要: 月間サマリー機能
- 要件: 支出合計、カテゴリ別内訳を表示
- 設計: バックエンド API + フロントエンド UI
- テスト: ロード時間、大量データでの動作確認
```

---

### フェーズ2: 実装（/add-feature）

```bash
# 機能の実装を開始
/add-feature feature-monthly-report
```

**実行内容**:
このコマンドが以下を自動実行します：

1. **.steering/[日付]-feature-monthly-report/** ディレクトリを作成
2. **ステアリングファイルの自動生成**:
   - `requirements.md` - 詳細な要件定義
   - `design.md` - 実装設計（API、DB、UI）
   - `tasklist.md` - タスク分割・優先度
3. **tasklist.md に従って実装を進行**:
   - 各タスク開始時に `[ ]` → `[x]` に更新（steering スキル使用）
   - 全タスク完了まで自動継続（スキップ不可）
4. **実装完了後**:
   - implementation-validator で品質検証
   - テスト・リント・型チェック実行
   - tasklist.md に振り返りを記録

**参考**: `.claude/commands/add-feature.md`

---

### フェーズ3: 完了処理

実装完了後：

```bash
# 1. アイデアファイルを done に移動
mv docs/ideas/pending/feature-monthly-report.md docs/ideas/done/

# 2. 永続ドキュメント更新（該当する場合）
# 例: 新機能を product-requirements.md に追記
# 例: アーキテクチャ変更があれば architecture.md を更新
```

**永続ドキュメント更新の判定**:
- ✅ 新しい機能が追加された → `product-requirements.md` 更新
- ✅ DB スキーマが変わった → `functional-design.md` 更新
- ✅ システム構成が変わった → `architecture.md` 更新
- ✅ コーディング規約を追加した → `development-guidelines.md` 更新

---

## ドキュメント層構造

| 層 | ファイル | 更新頻度 | 用途 | 対象者 |
|----|---------|---------|------|--------|
| **永続** | `docs/product-requirements.md` | 機能追加時 | プロジェクト全体の要件 | 全員 |
| **永続** | `docs/functional-design.md` | 機能追加時 | 機能仕様・データモデル | 開発者 |
| **永続** | `docs/architecture.md` | アーキテクチャ変更時 | システム構成・責任分離 | 開発者 |
| **永続** | `docs/development-guidelines.md` | 規約変更時 | コーディング規約・開発プロセス | 開発者 |
| **一時** | `docs/ideas/pending/feature-*.md` | 開発前 | アイデア設計・要件整理 | Claude Code |
| **一時** | `.steering/[日付]-機能名/` | 開発中 | 実装計画・進捗・タスク | Claude Code + 開発者 |
| **完了記録** | `docs/ideas/done/feature-*.md` | 開発完了後 | 実装済み機能の履歴・参考 | 全員 |

---

## セットアップチェックリスト

新規開発者はこれを確認してください：

- [ ] `/setup-project` で 6つの永続ドキュメントが作成されている
- [ ] `docs/ideas/README.md` を読み、ideas ワークフロー（フェーズ1）を理解した
- [ ] この CLAUDE.md を読み、全フェーズの流れを把握した
- [ ] 実装準備完了 → `/add-feature [機能名]` で開発開始可能

---

## よくある質問

**Q1: ideas フェーズは本当に必要？**  
A: はい。実装前に設計を詰めることで、実装中の手戻りを大幅に削減できます。詳細は `docs/ideas/README.md` を参照。

**Q2: /add-feature 実行中に仕様変更があった場合は？**  
A: `.steering/[日付]-機能名/design.md` を更新し、理由を記録してください。steering スキル（モード2）がサポートします。

**Q3: 永続ドキュメントの更新を忘れた場合は？**  
A: 次の機能開発時に一度に更新して大丈夫です。ただし、アーキテクチャ共有が必要な場合は優先してください。

**Q4: ideas ドキュメントはいつ削除する？**  
A: `docs/ideas/done/` に移動後、いつでも参照可能のため、削除せず保持することをお勧めします。プロジェクト履歴として価値があります。

---

## アイデア → 実装ワークフロー

このプロジェクトでは、新機能や改善案を体系的に管理するため、以下のプロセスを採用しています：

**詳細は** `docs/ideas/README.md` **を参照**：
- アイデアの命名規則（feature-/improvement-/bugfix-）
- テンプレート（概要・背景・要件・設計方針）
- ベストプラクティス（すべきこと・避けるべきこと）
- 実装完了前のチェックリスト

## よくあるタスク

- **新しい API エンドポイントを追加する**: `Http/Controllers/` にコントローラーを作成し、`src/routes/api.php` にルートを定義し、`Http/Requests/` で検証を追加します
- **新しい Filament リソースを追加する**: `php artisan make:filament-resource ResourceName` を使用して管理パネルリソースをスカフォールドします
- **スキーマを変更する**: `php artisan make:migration create_table_name` でマイグレーションを作成し、モデルを更新してから `php artisan migrate` を実行します
- **React コンポーネントを再利用する**: 一貫性を保つために Material-UI と既存のフォームパターン（React Hook Form + Zod）を活用します

## API 仕様・ドキュメント

### OpenAPI 仕様書

```
api/openapi.yaml
```

**用途**:
- Swagger UI で API ドキュメント表示
- フロントエンド開発者向けリファレンス
- API クライアント自動生成
- バックエンド実装の仕様確認

**詳細**: `api/README.md` を参照

---

## Claude Code の権限設定

このプロジェクトでは、Claude が自動実行中に以下の Bash コマンドの実行を許可しています：

### セットアップ
- `composer install` - PHP 依存をインストール
- `npm install` - Node 依存をインストール

### Laravel ファイル生成（php artisan make:* コマンド）
- `php artisan make:model *` - Eloquent モデル作成
- `php artisan make:controller *` - API コントローラ作成
- `php artisan make:migration *` - DB マイグレーション作成
- `php artisan make:request *` - Form Request（入力検証）作成
- `php artisan make:filament-resource *` - Filament 管理画面リソース作成

### DB 操作
- `php artisan migrate` - マイグレーション実行
- `php artisan migrate:rollback` - マイグレーション ロールバック
- `php artisan db:seed` - シーダー実行（テストデータ挿入）

### フロントエンド ビルド
- `npm run build` - Vite ビルド（本番用アセット生成）
- `npm run dev` - Vite 開発サーバー起動

### テスト・品質チェック（現状未実装）

**注**: 以下のコマンドは現在自動実行していません。将来的にテスト体制を整備した際に実装予定です：
```bash
npm run lint       # ESLint（JavaScript/TypeScript コード品質）
npm run typecheck  # TypeScript 型チェック
npm test           # Jest テスト
php artisan test   # PHPUnit テスト
```

必要に応じて、開発者が手動で実行してください。

---

## デバッグ

- **Laravel**: `.env` で `APP_DEBUG=true` を有効にして、詳細なエラーページとスタックトレースを表示します
- **React**: ブラウザの DevTools を使用します。TypeScript はビルド/コミット時の型安全性を保証します
- **データベース**: `http://localhost:9998` の PhpMyAdmin または コンテナから MySQL CLI でアクセスできます
- **ログ**: Laravel ログは `src/storage/logs/laravel.log` にあります

