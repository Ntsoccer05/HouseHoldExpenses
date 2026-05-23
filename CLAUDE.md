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

## 開発ワークフロー

このプロジェクトは **Superpowers スキル** を使って機能開発を進めます。
スキルは `.claude/skills/` に配置されており、Claude Code が自動的に読み込みます。

### フェーズ0: プロジェクト初期化（初回のみ）

```bash
/setup-project
```

対話的に `docs/` 内の永続ドキュメント（product-requirements, functional-design, architecture など）を作成します。参考: `.claude/commands/setup-project.md`

---

### 標準の機能開発フロー

| ステップ | スキル | 出力先 |
|---------|--------|--------|
| 1. 設計・要件整理 | `brainstorming` | `docs/superpowers/specs/YYYY-MM-DD-<topic>-design.md` |
| 2. 実装計画作成 | `writing-plans` | `docs/superpowers/plans/YYYY-MM-DD-<feature>.md` |
| 3. 計画を実行 | `subagent-driven-development`（推奨）または `executing-plans` | コード・テスト・コミット |
| 4. 完了処理 | ご自身で判断 | `git merge` / `git push` / PR 作成 |

**アイデアの蓄積**: `docs/ideas/pending/` に要件メモを残すことができます（命名規則: `feature-xxx.md`）。設計フェーズで `brainstorming` スキルがこの内容を取り込みます。完了後は `docs/ideas/done/` に移動してください。

---

### 補助スキル

| 状況 | スキル |
|------|--------|
| バグ・テスト失敗 | `systematic-debugging` |
| 実装前（TDD） | `test-driven-development` |
| 完了宣言の前 | `verification-before-completion` |
| コードレビュー依頼 | `requesting-code-review` |
| レビュー受け取り | `receiving-code-review` |
| 複数の独立した問題 | `dispatching-parallel-agents` |
| 機能ブランチの隔離 | `using-git-worktrees` |

---

### 永続ドキュメント更新の判定

実装完了後、以下の基準で `docs/` を更新してください：

- 新機能追加 → `docs/product-requirements.md`
- DB スキーマ変更 → `docs/functional-design.md`
- システム構成変更 → `docs/architecture.md`
- コーディング規約追加 → `docs/development-guidelines.md`
- API 変更 → `api/openapi.yaml`（`update-api-spec` スキル）
- DB 設計変更 → `docs/database-design.md`（`update-db-design` スキル）

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

### テスト・品質チェック

```bash
cd src
php artisan test          # PHPUnit（Unit + Feature）
./vendor/bin/pint         # Laravel Pint（PHP リンター）
npm run typecheck         # TypeScript 型チェック
npm run lint              # ESLint
```

**注**: フロントエンド（React）側の自動テスト（Jest 等）は未整備です。`test-driven-development` スキルに従って新機能にはテストを先に書いてください。バックエンド（PHP）は `php artisan test` が利用可能です。

---

## デバッグ

- **Laravel**: `.env` で `APP_DEBUG=true` を有効にして、詳細なエラーページとスタックトレースを表示します
- **React**: ブラウザの DevTools を使用します。TypeScript はビルド/コミット時の型安全性を保証します
- **データベース**: `http://localhost:9998` の PhpMyAdmin または コンテナから MySQL CLI でアクセスできます
- **ログ**: Laravel ログは `src/storage/logs/laravel.log` にあります

