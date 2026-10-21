# PHP 8.3 / Laravel 13 Upgrade Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** バックエンドを PHP 8.3 + Laravel 13 に上げ、テスト・実ブラウザ・セキュリティ確認を経て本番 (Lambda/Bref) に反映する。

**Architecture:** 1 ブランチで Laravel 10→11→12→13 を段階的に上げ、各段階でテスト全件を通す。アプリ構造（Kernel / ServiceProvider）は Laravel 10 形式を維持し、セキュリティに効く設定（session json / cache serializable_classes / PreventRequestForgery）だけを取り込む。

**Tech Stack:** PHP 8.3, Laravel 13, Filament 3.3, Sanctum 4, PHPUnit 12, Bref 3 (php-83 layer), Docker (php:8.3-fpm + MySQL)

仕様: `docs/superpowers/specs/2026-10-22-php83-laravel13-upgrade-design.md`

---

## 共通コマンド

テストは docker-compose の `app`（PHP）と `db_household`（MySQL）で実行する。

```bash
# 起動（Dockerfile 変更後は --build）
docker compose up -d --build app db_household
# composer（vendor は named volume なのでコンテナ内で実行する）
docker compose exec app composer <args>
# テスト
docker compose exec app php artisan test
```

以下「TEST」= `docker compose exec app php artisan test`。期待値は常に「Tests: N passed, 0 failed」。

---

### Task 0: ベースライン取得

- [ ] **Step 1:** `docker compose up -d --build app db_household` を実行し、`docker compose exec app php -v` が `PHP 8.2.35` を返すことを確認する。
- [ ] **Step 2:** テスト DB `householdExpensesApp_testing` が無ければ作成する:
  `docker compose exec db_household mysql -uroot -p"$DB_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS householdExpensesApp_testing; GRANT ALL ON householdExpensesApp_testing.* TO '$DB_USER'@'%';"`
- [ ] **Step 3:** TEST を実行し、成功数・失敗数を記録する（移行前から落ちているテストを把握するため）。
- [ ] **Step 4:** `docker compose exec app composer audit` の結果を記録する。

### Task 1: PHP 8.3 化（Laravel 10 のまま）

**Files:**
- Modify: `docker/php/Dockerfile:1-2`, `docker/production/Dockerfile:1-2` → `FROM php:8.3.36-fpm`（Docker Hub の最新 8.3 パッチ版を確認して固定）
- Modify: `src/composer.json` → `"php": "^8.3"`
- Modify: `.github/workflows/deploy-backend-serverless.yml:65` → `php-version: "8.3"`
- Modify: `infra/terraform/modules/lambda_bref/variables.tf:39` → `layer:php-83:24`（説明文の `php-82` 記述も `php-83` に）

- [ ] **Step 1:** 上記を変更し `docker compose up -d --build app` 後、`php -v` が `PHP 8.3.x` を返すこと。
- [ ] **Step 2:** `composer update --lock` で lock の platform 情報を更新。
- [ ] **Step 3:** TEST が Task 0 と同じ結果であること。

### Task 2: Laravel 11

> **実施時の変更:** Laravel 11 は 2026-03 にサポート終了しており、全バージョンが未修正の脆弱性を抱えるため
> Composer のセキュリティブロックでインストールできなかった。中間段階のためにブロックを無効化するのは避け、
> 10→12 に直接上げ、11/12 両方のガイド項目を Task 3 でまとめて対応した。

公式ガイド https://laravel.com/docs/11.x/upgrade の High/Medium 項目を対応する。

**Files:**
- Modify: `src/composer.json`:
  `laravel/framework:^11.0`, `laravel/sanctum:^4.0`, `nunomaduro/collision:^8.1`, `phpunit/phpunit:^11.0`,
  `spatie/laravel-ignition` は L11 対応版へ、`laravel/ui`・`laravel/socialite`・`barryvdh/laravel-debugbar`・`filament/filament` は対応版へ。
- Modify: `src/config/sanctum.php`（Sanctum 4 の skeleton と差分比較し、`middleware` キーの `authenticate_session` / `encrypt_cookies` / `validate_csrf_token` 形式に更新）

- [ ] **Step 1:** `composer require laravel/framework:^11.0 laravel/sanctum:^4.0 nunomaduro/collision:^8.1 --with-all-dependencies --no-update` → `composer require --dev phpunit/phpunit:^11.0 --no-update` → `composer update --with-all-dependencies`。解決できないパッケージは対応版を Packagist で調べて制約を更新する。
- [ ] **Step 2:** Carbon 3: `grep -rnE "diffIn|addMonth|subMonth" src/app` を確認し、整数を前提にしている箇所に `(int)` を補う（現状 diffIn* は 0 件）。
- [ ] **Step 3:** パスワード再ハッシュ・per-second レート制限・`Model::casts()` は互換のため変更不要であることを確認。
- [ ] **Step 4:** `php artisan filament:upgrade` を実行。
- [ ] **Step 5:** TEST 全件成功。

### Task 3: Laravel 12

ガイド https://laravel.com/docs/12.x/upgrade 。

- [ ] **Step 1:** `laravel/framework:^12.0`、`phpunit/phpunit:^11.5`（L12 は ^11.5.3）に更新し `composer update --with-all-dependencies`。
- [ ] **Step 2:** `HasUuids`（UUIDv7 化）、`image` ルールの SVG 除外、`Schema::getTables` 等が未使用であることを grep で確認（現状 0 件）。
- [ ] **Step 3:** TEST 全件成功。

### Task 4: Laravel 13 + セキュリティ設定

ガイド https://laravel.com/docs/13.x/upgrade 。

**Files:**
- Modify: `src/composer.json`: `laravel/framework:^13.0`, `laravel/tinker:^3.0`, `phpunit/phpunit:^12.0`
- Modify: `src/app/Http/Middleware/VerifyCsrfToken.php` → 親クラスを `Illuminate\Foundation\Http\Middleware\PreventRequestForgery` に変更（クラス名は Kernel の参照を壊さないため維持）
- Modify: `src/app/Providers/Filament/AdminPanelProvider.php:16,52` → `PreventRequestForgery` を参照
- Modify: `src/config/session.php` → `'serialization' => 'json',` を追加
- Modify: `src/config/cache.php` → `'serializable_classes' => false,` を追加
- Test: `src/tests/Feature/SecurityHardeningTest.php`（新規）

- [ ] **Step 1: 失敗するテストを書く**

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    public function test_session_uses_json_serialization(): void
    {
        $this->assertSame('json', config('session.serialization'));
    }

    public function test_cache_does_not_unserialize_arbitrary_classes(): void
    {
        $this->assertFalse(config('cache.serializable_classes'));
    }

    public function test_cross_site_state_changing_request_is_rejected(): void
    {
        $response = $this->withHeaders(['Sec-Fetch-Site' => 'cross-site'])
            ->post('/admin/login');

        $this->assertSame(419, $response->getStatusCode());
    }
}
```

- [ ] **Step 2:** TEST（`--filter=SecurityHardeningTest`）で 3 件とも FAIL することを確認（L12 状態のまま実行）。
- [ ] **Step 3:** composer を更新（`composer update --with-all-dependencies`）し、上記 Files の変更を行う。
- [ ] **Step 4:** `--filter=SecurityHardeningTest` が PASS。
- [ ] **Step 5:** TEST 全件成功。
- [ ] **Step 6:** CSRF の SPA 互換性: `vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php` を読み、`same-site`（localhost:5173→localhost:9000）と `same-origin`（本番 kake-pon.com）が許可される条件を確認する。不許可なら `config/` または middleware のプロパティで許可設定を追加する。

### Task 5: 品質・セキュリティ確認

- [ ] **Step 1:** `docker compose exec app ./vendor/bin/pint --test`（違反があれば `pint` で修正）。
- [ ] **Step 2:** `docker compose exec app composer audit` → 0 件。
- [ ] **Step 3:** `cd src && npm run typecheck && npm run build`。
- [ ] **Step 4:** `chrome-screen-check` スキルで主要画面（ログイン、カレンダー、支出 CRUD、グラフ、固定費、割り勘、/admin）を確認。
- [ ] **Step 5:** `security-review` スキルでブランチ差分をレビューし、指摘を修正。

### Task 5b: 管理画面のアクセス制御（実施中に発見した既存の脆弱性）

- `User::canAccessPanel()` が `@gmail.com` で終わる全ユーザーを許可していたため、`config('auth.admin_emails')`
  （env `ADMIN_EMAILS`、既定 `2460socceronly0512@gmail.com`）に載ったメール認証済みユーザーだけに限定。
- テスト: `src/tests/Feature/AdminPanelAccessTest.php`。

### Task 6: ドキュメント

- [ ] **Step 1:** `CLAUDE.md`、`docs/architecture.md` の「Laravel 10」「PHP 8.2」記載を更新。

### Task 7: 最終コミットと本番反映

- [ ] **Step 1:** `git add -A && git commit -m "feat: PHP 8.3 / Laravel 13 へアップグレード"`
- [ ] **Step 2:** 切り戻し情報を記録: 現行 main の SHA（`backend/<sha>.zip`）、現行レイヤー `php-82:26`。
- [ ] **Step 3:** RDS 稼働時間帯に、先に api / console 両 Lambda のレイヤーを `php-83:24` に `aws lambda update-function-configuration --layers arn:aws:lambda:ap-northeast-1:873528684822:layer:php-83:24`（Bref 3 の公開アカウント。534081306603 は旧アカウントなので不可）で切り替え、現行コード（Laravel 10）が PHP 8.3 で動くことを `/api/health` で確認する。逆順（コード先行）だと新コードの composer platform check (PHP ^8.3) が PHP 8.2 上で失敗し全停止するため、必ずこの順にする。
- [ ] **Step 4:** main へマージ・push → `deploy-backend-serverless` の成功を `gh run watch` で確認。
- [ ] **Step 4b:** Filament/Livewire の静的アセットは Lambda ではなく S3 `house-hold-api-static-assets` から配信されており CI では同期されないため、
  `aws s3 sync src/public/css/filament s3://house-hold-api-static-assets/css/filament`、同様に `js/filament`・`vendor` を同期し、
  さらに `src/public/vendor/livewire/livewire.js` と `livewire.min.js.map` を `s3://house-hold-api-static-assets/livewire/` にもコピーし、
  CloudFront の `/css/filament/*` `/js/filament/*` `/vendor/*` `/livewire/livewire.js*` を invalidation する（Livewire 3.8 の XSS 修正はクライアント JS 側にあるため必須）。
- [ ] **Step 5:** 本番確認: `/api/health` 200、Lambda ログにエラーなし、API Gateway 5xx 増加なし、/admin ログイン画面表示、主要画面の表示。
- [ ] **Step 6:** 異常時は切り戻し（旧アーティファクト + `php-82:26`）。
