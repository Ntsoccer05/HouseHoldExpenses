# PHP 8.3 移行 / Laravel 13 アップグレード 設計書

- 作成日: 2026-10-22
- ブランチ: `feature/php83-laravel13`

## 背景・目的

- 2026-10-21 の PHP 重大脆弱性 php2shell (CVE-2026-109618/109619) 対応で PHP 8.2.35 へ緊急更新した。
- しかし PHP 8.2 は 2026-12-31 にサポート終了、Laravel 10 は 2025-02 にセキュリティサポート終了済みで、
  フレームワーク側の脆弱性には今後パッチが出ない。
- Laravel 11 も 2026-03-12 にサポート終了、Laravel 12 はセキュリティ修正が 2027-02-24 までのため、
  **Laravel 13（セキュリティ修正 2028 Q1 まで、PHP 8.3〜8.5）** を移行先とする。

## 成功基準

1. 本番 (Lambda/Bref) が PHP 8.3 + Laravel 13 で稼働し、既存機能がすべて従来どおり動く。
2. `php artisan test` が全件成功する（PHP 8.3 環境）。
3. 実ブラウザで主要画面（ログイン、カレンダー、支出 CRUD、グラフ、固定費、割り勘、Filament 管理画面）が動く。
4. `composer audit` で既知脆弱性 0 件。`security-review` で重大指摘なし。
5. DB スキーマは変更しない（切り戻しをコード＋レイヤーだけで完結させるため）。

## 現状

| 項目 | 現在 | 移行後 |
|---|---|---|
| PHP | 8.2.35 | 8.3.36 以降 |
| laravel/framework | 10.48 | ^13.0 |
| filament/filament | 3.3.14 | 3.3 系最新 (3.3.57 は illuminate ^13 対応) |
| laravel/sanctum | 3.3 | ^4.0 |
| laravel/tinker | 2.x | ^3.0 |
| phpunit/phpunit | 10.5 | ^12.0 |
| nunomaduro/collision | 7.x | ^8.0 |
| bref/laravel-bridge | 3.0.8 | 3.1 系 (illuminate ^13 対応) |
| Bref レイヤー | php-82:26 | php-83:24 |

調査で影響なしと確認したもの: マイグレーションの `->change()`、Doctrine DBAL、Carbon の `diffIn*`、`upsert`、
キャッシュプレフィックス／セッション Cookie 名（アプリ側 config で明示済みのため L13 の既定値変更の影響を受けない）。

## 方針

### アプローチ: 1 ブランチで 10→11→12→13 を段階的に上げる

各段階で composer 更新 → 公式アップグレードガイドの該当項目対応 → テスト全件成功 → コミット、の順で進める。
失敗時にどのメジャーの変更が原因か切り分けられるようにするため。

### アプリ構造は Laravel 10 形式を維持

`app/Http/Kernel.php`、`app/Console/Kernel.php`、各 ServiceProvider は Laravel 11 以降も公式サポートされる。
スリム構成（`bootstrap/app.php` 集約）への作り替えは目的外のため行わない。config ファイルも現状を維持し、
セキュリティに効く差分のみ取り込む。

### Laravel 13 で取り込むセキュリティ設定

- `config/session.php`: `serialization => 'json'`（デプロイ時に全ユーザーが一度ログアウトされることを許容済み）
- `config/cache.php`: `serializable_classes => false`（キャッシュに PHP オブジェクトを保存していないことを確認したうえで）
- CSRF ミドルウェアを `PreventRequestForgery` に移行（`App\Http\Middleware\VerifyCsrfToken` と Filament の
  `AdminPanelProvider` の参照を更新）。`Sec-Fetch-Site` による送信元検証が SPA（同一オリジン: kake-pon.com、
  ローカル: localhost:5173 → localhost:9000）を壊さないことをテストと実ブラウザで確認する。

### 依存パッケージ

- 上表のとおり更新。Laravel 13 非対応のパッケージ（例: spatie/laravel-ignition、laravel/ui 等）は
  対応版へ更新し、対応版が無い場合は利用箇所を確認のうえ削除する。

### インフラ

- `docker/php/Dockerfile`、`docker/production/Dockerfile`: `php:8.3.x-fpm`（パッチ版固定）
- `infra/terraform/modules/lambda_bref/variables.tf`: `bref_layer_arn` を `php-83:24` に
- `.github/workflows/deploy-backend-serverless.yml`: `php-version: "8.3"`
- `composer.json`: `"php": "^8.3"`

## テスト

1. 段階ごとに PHP 8.3 コンテナ + docker-compose の MySQL で `php artisan test` 全件。
2. 移行で挙動が変わる箇所（CSRF 送信元検証、セッション json 化、Sanctum 4 の SPA 認証）にテストが無ければ追加する。
3. `./vendor/bin/pint --test`、`npm run typecheck`、`npm run build`。
4. `chrome-screen-check` スキルで主要画面を実ブラウザ確認（コンソールエラー・ネットワークエラーなし）。

## セキュリティ確認

1. `composer audit` 0 件。
2. `security-review` スキルで差分レビュー。
3. デプロイ後: Lambda ログのエラー、API Gateway の 4xx/5xx、レスポンスヘッダ、Filament 管理画面の認証を確認。

## 本番反映と切り戻し

- RDS 稼働時間帯（平日 6:00-11:00 / 17:00-翌1:00、土日祝 7:00-翌1:00 JST）に main へマージしてデプロイする。
- デプロイ前に記録しておくもの: 現行コードの S3 アーティファクトキー（`backend/<sha>.zip`）、現行レイヤー `php-82:26`。
- 切り戻し: 旧アーティファクトで `update-function-code`、レイヤーを `php-82:26` に戻す（api / console 両方）。
- Bref レイヤーは Terraform の `terraform apply` ではなく、これまで同様 `aws lambda update-function-configuration --layers`
  で切り替え、Terraform の default 値も同じ値に揃える（他の Terraform 差分を巻き込まないため）。
- 想定外に DB マイグレーションが必要になった場合は、本番適用前にユーザーへ確認する。

## ドキュメント更新

- `docs/architecture.md`、`docs/development-guidelines.md`（存在すれば）のバージョン記載。
- CLAUDE.md の「Laravel 10」記載。

## スコープ外

- Filament 4/5 へのメジャーアップグレード（Filament 3.3 系は Laravel 13 対応済みのため今回は見送り）。
- スリム構成への作り替え、フロントエンドの依存更新。
- API Gateway アクセスログ有効化（別タスクとして提案済み）。
