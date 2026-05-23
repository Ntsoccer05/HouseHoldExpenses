# 開発ワークフロー（Superpowers）

このプロジェクトは **Superpowers スキル** を使って機能開発を進めます。
スキルは `.claude/skills/` に配置されており、Claude Code が会話の流れに応じて自動的に適用します。

---

## 全体フロー

```
アイデア
  ↓
[brainstorming] 設計・要件整理 → docs/superpowers/specs/
  ↓
[writing-plans] 実装計画作成 → docs/superpowers/plans/
  ↓
[subagent-driven-development] タスク実行（推奨）
  または
[executing-plans] タスク実行（同一セッション）
  ↓
マージ / PR / ブランチ保持（ご自身で判断）
```

---

## フェーズ1：設計（brainstorming）

### いつ使う
新機能・改善案を思いついたとき。コードを書く前に**必ず**このフェーズを通します。

### 始め方
Claude Code のチャットでやりたいことを話しかけるだけです。

```
「月次レポートの CSV エクスポート機能を作りたい」
「支出の一括削除ができるようにしたい」
「ログイン画面のデザインを改善したい」
```

### Claude がすること
1. `src/` のコード・`docs/` のドキュメント・最近のコミットを確認
2. 一問ずつ質問して要件を明確化
3. 2〜3 のアプローチを提案（トレードオフ付き）
4. 設計をセクションごとに提示して承認を得る
5. 設計書を `docs/superpowers/specs/YYYY-MM-DD-<topic>-design.md` に保存・コミット

### アイデアの事前メモ
設計フェーズより先にアイデアをメモしておきたい場合は `docs/ideas/pending/` を使えます。
命名規則: `feature-<機能名>.md` / `improvement-<改善名>.md` / `bugfix-<バグ名>.md`

完了後は `docs/ideas/done/` に移動してください。

---

## フェーズ2：実装計画（writing-plans）

### いつ使う
設計が承認された直後。`brainstorming` スキルが自動的に移行します。

### Claude がすること
1. 設計書を読み込み、ファイル構造を定義
2. TDD サイクル付きのタスクに分解（各タスク 2〜5 分の粒度）
3. 計画を `docs/superpowers/plans/YYYY-MM-DD-<feature>.md` に保存
4. 実行方法の選択肢を提示

### 計画のタスク例（Laravel API 追加の場合）

```markdown
### Task 1: エクスポートコントローラー

**Files:**
- Create: `src/app/Http/Controllers/ExportController.php`
- Create: `src/tests/Feature/ExportControllerTest.php`

- [ ] Step 1: テストを書く（失敗することを確認）
- [ ] Step 2: php artisan test でテストが失敗することを確認
- [ ] Step 3: 最小限の実装を書く
- [ ] Step 4: php artisan test でテストが通ることを確認
- [ ] Step 5: コミット
```

---

## フェーズ3：実装（subagent-driven-development）

### 推奨: subagent-driven-development

タスクごとに専用サブエージェントを起動し、2 段階レビューを自動実行します。

```
各タスク:
  実装サブエージェント → 仕様準拠レビュー → コード品質レビュー → 完了
```

**特徴**:
- 文脈汚染なし（各タスクが独立）
- 2 段階の自動品質チェック
- 中断不要で全タスク連続実行

### 代替: executing-plans

同一セッションでチェックポイントを設けながら実行します。

```
[blocker発生] → 即停止 → ユーザーに確認 → 再開
```

### バックエンド実装のポイント（Laravel）

```bash
# テスト実行（TDD の要）
cd src
php artisan test

# 新規ファイル生成
php artisan make:controller ExportController
php artisan make:model Budget -m          # マイグレーション付き
php artisan make:request ExportRequest
php artisan make:filament-resource Budget # Filament 管理画面

# DB 操作
php artisan migrate
php artisan migrate:rollback
php artisan db:seed

# コード品質
./vendor/bin/pint        # スタイルチェック
./vendor/bin/pint --fix  # 自動修正
```

### フロントエンド実装のポイント（React/TypeScript）

```bash
# 開発サーバー
cd src && npm run dev    # http://localhost:5173

# 品質チェック
npm run typecheck        # TypeScript 型チェック
npm run lint             # ESLint

# ビルド確認
npm run build
```

### 新しい API エンドポイントを追加する場合

1. `src/app/Http/Controllers/` にコントローラー作成
2. `src/routes/api.php` にルート定義
3. `src/app/Http/Requests/` にバリデーション追加
4. **`api/openapi.yaml` を `update-api-spec` スキルで更新**
5. フロントエンド側（`src/resources/js/` または FrontendHouseHoldExpenses）の API クライアントを更新

### DB スキーマを変更する場合

1. `php artisan make:migration` でマイグレーション作成
2. `php artisan migrate` を実行
3. モデルを更新
4. **`docs/database-design.md` を `update-db-design` スキルで更新**

---

## フェーズ4：完了処理（ご自身で判断）

全タスク完了後、以下を確認してからブランチを処理してください。

**完了前に必ず確認**:
- `php artisan test` がすべてパス
- `npm run typecheck` がエラーなし
- `./vendor/bin/pint` がクリーン

**完了方法（任意で選択）**:
```bash
# ローカルマージ
git checkout main && git merge <branch>

# PR 作成
git push -u origin <branch>
gh pr create

# ブランチをそのまま保持
git push -u origin <branch>
```

---

## 補助スキル

### バグが出たとき → systematic-debugging

```
「このエラーを直して」
「テストが通らない」
「画面が真っ白になる」
```

**4 フェーズ**: 根本原因調査 → パターン分析 → 仮説とテスト → 実装
**禁止**: 「とりあえず直してみる」「複数の変更を同時に試す」

### 新機能実装前 → test-driven-development

```
RED（失敗テストを書く） → GREEN（最小限の実装） → REFACTOR（整理）
```

Laravel では `php artisan test` で確認しながら進めます。

### 完了を宣言する前 → verification-before-completion

「動いてると思います」は禁止。必ずコマンドを実行して出力を確認してから報告。

```bash
php artisan test        # 0 failures であることを確認
npm run typecheck       # エラーなしを確認
./vendor/bin/pint       # スタイル問題なしを確認
```

### コードレビュー → requesting-code-review / receiving-code-review

PR 作成前・主要機能完了後にレビューサブエージェントを起動してチェック。

### 複数バグ同時発生 → dispatching-parallel-agents

独立した問題を並列エージェントで同時調査。

### 機能ブランチを切る → using-git-worktrees

main ブランチを汚さずに安全な作業環境を確保。

---

## ドキュメント管理

| 種別 | 場所 | 管理スキル |
|------|------|-----------|
| 設計書（仕様） | `docs/superpowers/specs/` | `brainstorming` |
| 実装計画 | `docs/superpowers/plans/` | `writing-plans` |
| アイデアメモ | `docs/ideas/pending/` → `docs/ideas/done/` | 手動 |
| API 仕様 | `api/openapi.yaml` | `update-api-spec` |
| DB 設計 | `docs/database-design.md` | `update-db-design` |
| 要件定義 | `docs/product-requirements.md` | `prd-writing` |
| 機能設計 | `docs/functional-design.md` | `functional-design` |
| アーキテクチャ | `docs/architecture.md` | `architecture-design` |
| 用語集 | `docs/glossary.md` | `glossary-creation` |

---

## よくあるシナリオ

### シナリオ 1: 新しい API エンドポイント + フロントエンド UI を追加

```
1. brainstorming でバックエンド仕様・フロントエンド UI を同時設計
2. writing-plans でバックエンドタスクとフロントエンドタスクを分けて計画
3. subagent-driven-development でバックエンド → フロントエンドの順に実装
   （バックエンド先行で API を確定させてからフロントを実装）
4. update-api-spec で openapi.yaml を更新
5. `git push` して PR 作成
```

### シナリオ 2: バグを発見した

```
1. systematic-debugging で根本原因を特定
2. test-driven-development で失敗テストを先に書く
3. 修正を実装
4. php artisan test で全テストがパスすることを確認
5. verification-before-completion で完了宣言
```

### シナリオ 3: 複数のテスト失敗が同時発生

```
1. 失敗が独立しているかを判断
2. 独立していれば dispatching-parallel-agents で並列調査
3. 各エージェントが根本原因を特定・修正
4. php artisan test で全体確認
```

### シナリオ 4: Filament 管理画面に新しいリソースを追加

```
1. brainstorming で管理画面の仕様を設計
2. writing-plans で以下のタスクに分解:
   - マイグレーション作成
   - モデル作成
   - Filament リソース作成
   - テスト作成
3. subagent-driven-development で実装
4. update-db-design で DB 設計書を更新
```

---

## プロジェクト固有のコマンドリファレンス

### Docker 環境

```bash
docker-compose up -d      # MySQL、Nginx、Mailhog 起動
docker-compose down       # 停止
```

### サーバー起動（開発時）

```bash
# ターミナル 1: Docker
docker-compose up -d

# ターミナル 2: Laravel
cd src && php artisan serve --port=9000

# ターミナル 3: Vite（React HMR）
cd src && npm run dev
```

| サービス | URL |
|---------|-----|
| Laravel API | http://localhost:9000 |
| React フロントエンド | http://localhost:5173 |
| Filament 管理画面 | http://localhost:9000/admin |
| Mailhog（メール確認） | http://localhost:8025 |
| PhpMyAdmin | http://localhost:9998 |
