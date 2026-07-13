# カケポン（HouseHold Expenses）バックエンド

家計簿・支出管理アプリケーション「カケポン」のバックエンドリポジトリです。収支管理・固定費管理・カテゴリ管理・世帯内での支出の割り勘（分割）機能などを提供する API と、管理パネルを実装しています。

- **バックエンド**: Laravel 10（API）+ Filament 3（管理パネル）
- **データベース**: MySQL 8.0
- **認証**: Laravel Sanctum（API トークン認証）+ Laravel Socialite（SNS ログイン連携）
- **インフラ**: Docker（ローカル開発）/ AWS ECS（本番、GitHub Actions で CI/CD）

> フロントエンド（React）は別リポジトリで管理されています。

---

## 目次

1. [主な機能](#主な機能)
2. [技術スタック](#技術スタック)
3. [ディレクトリ構成](#ディレクトリ構成)
4. [セットアップ](#セットアップ)
5. [開発の始め方](#開発の始め方)
6. [テスト・コード品質](#テストコード品質)
7. [API 仕様](#api-仕様)
8. [ドキュメント](#ドキュメント)
9. [開発ワークフロー（Claude Code / Superpowers）](#開発ワークフローclaude-code--superpowers)
10. [CI/CD](#cicd)

---

## 主な機能

- 収入・支出データの登録／編集／削除 API
- 収入・支出カテゴリのカスタマイズ管理
- 固定費（家賃、サブスクなど）の登録と履歴管理
- 世帯・グループ単位での支出の割り勘（Split Group）と精算
- SNS ログインを含む認証機能（Laravel Sanctum + Socialite）
- Filament 管理パネルによるアカウント・コンテンツ管理

## 技術スタック

| 技術 | バージョン | 用途 |
|---|---|---|
| PHP | ^8.1 | サーバーサイド言語 |
| Laravel | ^10.10 | Web フレームワーク（API） |
| Laravel Sanctum | ^3.3 | API 認証（SPA トークン） |
| Laravel Socialite | ^5.15 | SNS ログイン連携 |
| Filament | ^3.3 | 管理パネル |
| MySQL | 8.0 | データベース |
| Laravel Pint | ^1.0 | PHP コードフォーマッター |
| PHPUnit | ^10.1 | テストフレームワーク |

### 開発・インフラ
- Docker / Docker Compose（PHP, Nginx, MySQL, Mailhog, phpMyAdmin）
- GitHub Actions（`main` ブランチへの push で AWS ECS に自動デプロイ）

## ディレクトリ構成

```
HouseHoldExpenses/
├── src/                     # Laravel アプリケーション本体
│   ├── app/
│   │   ├── Http/Controllers # API コントローラ
│   │   ├── Http/Requests    # フォームリクエスト（バリデーション）
│   │   ├── Http/Services    # ビジネスロジック
│   │   ├── Models           # Eloquent モデル
│   │   └── Filament         # 管理パネルリソース
│   ├── routes/               # ルーティング定義（api.php など）
│   ├── database/            # マイグレーション・シーダー
│   └── tests/                # PHPUnit テスト
├── api/                     # OpenAPI 3.0 仕様書（api/openapi.yaml ほか）
├── docs/                    # 永続ドキュメント（アーキテクチャ、DB 設計など）
├── docker/                  # Dockerfile 群（PHP, Nginx, 本番用）
├── infra/scheduler/         # 定期実行バッチ（AWS Lambda）用インフラ定義
├── .github/workflows/       # CI/CD（GitHub Actions）
└── docker-compose.yml       # ローカル開発用マルチコンテナ構成
```

## セットアップ

### 前提条件
- Docker / Docker Compose
- Composer（コンテナ内で実行するため、ローカルには必須ではありません）

### 手順

```bash
# 1. リポジトリのルートに .env を用意
cp .env.example .env  # 必要に応じて値を編集

# 2. Docker コンテナを起動（MySQL / Nginx / Mailhog / phpMyAdmin など）
docker-compose up -d

# 3. src/ に移動して依存関係をインストール
cd src
cp .env.example .env  # DB・メールなどの接続情報を必要に応じて編集
composer install

# 4. アプリケーションキーの生成とマイグレーション
php artisan key:generate
php artisan migrate
php artisan db:seed  # テスト用データを投入する場合
```

## 開発の始め方

```bash
# ターミナル 1: Docker コンテナを起動（MySQL、Nginx、Mailhog）
docker-compose up -d

# ターミナル 2: Laravel サーバーを起動（src/ 配下で実行）
cd src
php artisan serve --port=9000
```

| サービス | URL |
|---|---|
| Laravel API | http://localhost:9000 |
| Filament 管理パネル | http://localhost:9000/admin |
| Mailhog（メール確認） | http://localhost:8025 |
| phpMyAdmin | http://localhost:9998 |

`.env` の `CLIENT_URL` にフロントエンドの URL（例: `http://localhost:5173`）を設定することで、フロントエンドからの認証付きリクエストを受け付けられるよう CORS / Sanctum のステートフルドメインが構成されています。

## テスト・コード品質

```bash
cd src

# PHPUnit（Unit + Feature テスト）
php artisan test
php artisan test tests/Unit               # Unit テストのみ
php artisan test tests/Unit/SomeTest.php  # 特定のテストのみ

# Laravel Pint（PHP コードスタイル）
./vendor/bin/pint         # チェックのみ
./vendor/bin/pint --fix   # 自動修正
```

## API 仕様

API 仕様は OpenAPI 3.0 形式で `api/openapi.yaml` に定義されています。詳細な構成やメンテナンス方法は [`api/README.md`](./api/README.md) を参照してください。

```bash
# Docker で Swagger UI を起動して確認
docker run -p 8081:8080 \
  -v $(pwd)/api:/usr/share/nginx/html/api \
  -e BASE_URL=/api/openapi.yaml \
  swaggerapi/swagger-ui
# http://localhost:8081 にアクセス
```

## ドキュメント

`docs/` 配下に、コードから読み取れない設計判断や背景をまとめた永続ドキュメントを管理しています。

| ドキュメント | 内容 |
|---|---|
| [`docs/architecture.md`](./docs/architecture.md) | 技術スタック選定理由、レイヤー構成、システム構成図 |
| [`docs/database-design.md`](./docs/database-design.md) | テーブル定義・ER 図・インデックス設計 |
| [`docs/development-workflow.md`](./docs/development-workflow.md) | 開発の進め方（Superpowers スキルを使ったフロー） |
| [`docs/ideas/`](./docs/ideas) | 機能アイデア・要望のストック（`pending/` → `done/`） |
| [`docs/superpowers/`](./docs/superpowers) | 各機能の設計書・実装計画 |

## 開発ワークフロー（Claude Code / Superpowers）

本プロジェクトは Claude Code と「Superpowers スキル」（`.claude/skills/`）を用いた機能開発フローを採用しています。詳細は [`CLAUDE.md`](./CLAUDE.md) を参照してください。

| ステップ | スキル | 出力先 |
|---|---|---|
| 1. 設計・要件整理 | `brainstorming` | `docs/superpowers/specs/` |
| 2. 実装計画作成 | `writing-plans` | `docs/superpowers/plans/` |
| 3. 計画を実行 | `subagent-driven-development` / `executing-plans` | コード・テスト・コミット |
| 4. 完了処理 | - | `git merge` / `git push` / PR 作成 |

## CI/CD

`main` ブランチへの push をトリガーに GitHub Actions（[`.github/workflows/cicd.yml`](./.github/workflows/cicd.yml)）が実行され、Docker イメージをビルドして AWS ECS にデプロイします。また `infra/scheduler/` には、AWS Lambda を用いた定期実行バッチ（固定費の自動登録など）のインフラ定義（CloudFormation）が含まれています。
