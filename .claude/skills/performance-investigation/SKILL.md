---
name: performance-investigation
description: Use when an API endpoint or page is reported as slow ("遅い", "レイテンシが高い") and you need to find the root cause before proposing a fix - measures actual query count/timing via Laravel query log, reads MySQL EXPLAIN/EXPLAIN ANALYZE plans, and distinguishes N+1 queries from non-sargable predicates from unbounded result sets
---

# APIレイテンシの調査方法

## 概要

「遅い」という報告に対して、コードを眺めて推測で直すのは危険。**まず実データで再現し、クエリ数と実行計画という客観的な証拠を取ってから**原因を特定し、直す。

**核心原則:** 体感や勘ではなく、クエリログとEXPLAINの数字で原因を特定する。「たぶんN+1」ではなく「882クエリ発行されている」まで確認してから直す。

## 使うタイミング

- 特定のAPIエンドポイントやページが遅いと報告されたとき
- 大量データ環境（本番相当）でのみ顕在化する遅延を調べるとき
- パフォーマンス改善のPR/実装前の原因特定として

## 前提: 現実的なデータ量で再現する

少量データではN+1問題も非効率なクエリも顕在化しない。`test-kakep@example.com`（`TestAccountSeeder`で作成、約1万件の家計簿データ）のような、本番相当のボリュームを持つテストアカウントで検証する。データが足りない場合はまず増やす。

## 調査手順

### 1. 対象コードパスを特定する

エンドポイントのURLから route → controller → model の呼び出し連鎖を追う。

```bash
grep -n "対象のパス" routes/api.php
```

### 2. クエリ数と所要時間を実測する

HTTP経由でcurlすると認証やミドルウェアのオーバーヘッドが混ざるため、`php artisan tinker` でコントローラー/モデルのメソッドを直接呼び、`DB::enableQueryLog()` でクエリ数を、`microtime(true)` で所要時間を測る。これが最も重要なステップ — ここで「何本のクエリが飛んでいるか」を数値化する。

```bash
docker exec <app_container> php artisan tinker --execute="
use Illuminate\Support\Facades\DB;
DB::enableQueryLog();
\$start = microtime(true);
// 対象のコントローラー/モデルメソッドを直接呼ぶ
\$result = (new App\Http\Controllers\XxxController())->someMethod(\$request, ...);
\$elapsed = (microtime(true) - \$start) * 1000;
echo 'elapsed_ms=' . round(\$elapsed,2) . PHP_EOL;
echo 'query_count=' . count(DB::getQueryLog()) . PHP_EOL;
"
```

**クエリ数がリクエストの入力サイズ（対象件数）に比例して増える場合、ほぼ確実にN+1。** 件数を変えて再測定し、線形に増えるか確認するとさらに確実。

### 3. 疑わしいクエリをEXPLAIN / EXPLAIN ANALYZEで見る

Windows PowerShell経由で `docker exec ... php artisan tinker --execute="..."` にSQLの二重引用符を埋め込むと、PowerShellのクォート解釈でパースエラーになりやすい。**PHPスクリプトファイルを作り、`docker cp` でコンテナに入れてから `tinker --execute="include '/tmp/xxx.php';"` で実行する**のが確実。

```php
// scratchpad/explain.php
use Illuminate\Support\Facades\DB;

$plan = DB::select('EXPLAIN ANALYZE SELECT ... WHERE ...');
foreach ($plan as $row) { foreach ($row as $v) { echo $v . PHP_EOL; } }
```

```bash
docker cp scratchpad/explain.php <app_container>:/tmp/explain.php
docker exec <app_container> php artisan tinker --execute="include '/tmp/explain.php';"
```

見るポイント:
- `type`: `ALL`(フルスキャン) / `ref`・`range`(インデックス利用) など
- `key`: 意図したインデックスが使われているか
- `rows` / `EXPLAIN ANALYZE`の`actual rows`: 見積もり・実測の走査行数が、本来必要な行数と比べて過大でないか
- `Extra`: `Using filesort`, `Using temporary` などの危険信号

### 4. インデックス構成を確認する

```sql
SHOW INDEX FROM <table>;
```
想定している複合インデックスの列順とクエリの`WHERE`句の列順が一致しているか確認する。

## 典型的な原因パターンと解決法

### パターンA: N+1クエリ（ループ内で毎回クエリ）

**症状:** クエリ数が結果件数のほぼ2倍・3倍など、件数に比例して増える。

```php
// 悪い例: 1件ごとに関連データを2回クエリ
foreach ($rows as $row) {
    $name = SomeModel::where('id', $row->foreign_id)->value('name');
    $icon = SomeModel::where('id', $row->foreign_id)->value('icon');
}
```

**解決:**
- Eloquentのリレーションが使えるなら `with()` でEager Loading
- リレーションが素直に組めない/1リクエスト内で同じ参照先が繰り返し出る場合は、**リクエスト内キャッシュ**（静的プロパティにid単位で結果をメモ化）で同一キーへの再クエリを防ぐ
- あるいは1回のJOIN/サブクエリにまとめる

### パターンB: 非SARGableな述語（列を関数で包む）

**症状:** インデックスがあるのに `type=ALL` や、複合インデックスの一部しか使われない（`key_len`が短い）。

```php
// 悪い例: recorded_at を関数で包むとインデックスの当該部分が使えない
->whereRaw('DATE_FORMAT(recorded_at, "%Y%m") = ?', [$yearMonth])
```

**解決:** 範囲検索に書き換えて列を素のまま比較する。

```php
$start = Carbon::createFromFormat('Ym', $yearMonth)->startOfMonth();
$end = $start->copy()->endOfMonth();
->whereBetween('recorded_at', [$start, $end])
```

Laravelの `whereYear()` / `whereMonth()` も内部的に列を関数で包むため、同様の問題を起こしうる点に注意。

### パターンC: 無制限の全件取得（ページング/期間フィルタなし）

**症状:** `where('user_id', $id)->get()` のように、対象を絞らず全件取得している。データが増えるほど際限なく遅くなる。

**解決:** ページネーション（`paginate()`）や期間フィルタを追加する。**ただしAPIのレスポンス形が変わるとフロントエンドとの契約が変わるため、フロント側の対応が必要か必ず確認してから着手する。**

### パターンD: 使われていないEager Load

`with(['relation'])` しているのに、後続処理でそのリレーションを一度も参照していない場合は単純に無駄なクエリ。削除する。

## 修正の検証

1. 修正前と**同じ計測方法**（tinkerでのクエリ数・elapsed_ms）で再測定し、Before/Afterを数値で比較する
2. `php artisan test` を実行し、既存テストが壊れていないか確認する
3. テスト失敗が出た場合、`git stash push -- <変更ファイル>` で修正を退避してから同じテストを実行し、**修正前から失敗していたか**を切り分ける。無関係な既存の失敗を自分の修正のせいだと誤認しない
4. 検証が終わったら `git stash pop` で戻す

### ⚠️ `php artisan test` を実行する前に、テストDBが開発用DBと分離されているか必ず確認する

`RefreshDatabase` を使うFeatureテストは実行のたびに対象DBを `migrate:fresh` 相当で洗い流す。テスト用DBが正しく分離されていないと、**検証のつもりで実行したテストが開発用DBのデータ（シードしたテストアカウントや動作確認用データ）を全部消す**。実際にこのプロジェクトで、`.env.testing` を用意したのに気づかず `php artisan test` を2回実行し、seed済みのテストアカウントと1万件のデータを消してしまった事故があった。

原因は docker-compose.yml の `app` サービスが `environment:` と `env_file: .env` で `DB_DATABASE` 等を**コンテナの実環境変数として**注入していたこと。PHPのputenv()（`.env.testing` や phpunit.xml の `<env force="true">`）は既に `$_ENV`/`$_SERVER` に乗っている値を上書きできないため、これらの対策は静かに効かず、テストが開発用DBに接続し続けていた。

**確認方法（実行前に必ず1回やる）:**
```bash
# 開発用DBに目印レコードを作る
php artisan tinker --execute="App\Models\User::factory()->create(['email'=>'sentinel@example.com']);"
# 適当な1テストだけ流す
php artisan test --filter=<何か軽いテスト>
# 目印が生き残っているか確認する
php artisan tinker --execute="echo App\Models\User::where('email','sentinel@example.com')->exists() ? 'OK: 残っている' : 'NG: 消えた！';"
```
「NG」が出たら、テストが本物のDBに向いている。`.env.testing` やphpunit.xmlの`<env>`だけで安心せず、**`tests/CreatesApplication.php` の `createApplication()` 内で直接 `config(['database.connections.mysql.database' => '<テスト用DB名>'])` を上書きする**のが、環境変数の優先順位に左右されない確実な方法（このプロジェクトで実際に効いた対策）。

## 実例（このプロジェクトでの調査ログ）

`GET /api/monthly-transactions-bulk` と `GET /api/getTransactions` が遅いと報告された際の実測値。

| 対象 | 修正前 | 修正後 |
|---|---|---|
| `monthly-transactions-bulk`（4ヶ月・約1,700件） | 2,457ms / 2,599クエリ | 391ms / 12クエリ |
| `getTransactions`（全件・10,000件） | 16,285ms / 20,002クエリ | 1,534ms / 9クエリ |

原因は `Content::formatedTransaction()` が取引1件ごとにカテゴリ名・アイコンを2回別クエリで取得していたこと（パターンA）と、月次絞り込みが `whereRaw('DATE_FORMAT(recorded_at, "%Y%m") = ?', ...)` で非SARGableだったこと（パターンB）。EXPLAIN ANALYZEでは前者が走査行数4,930行（本来440行で足りる）というコスト差として、後者は「クエリ数がリクエストごとの取引件数にほぼ比例する」という形で現れた。

`getTransactions` は期間フィルタ・ページングが無く全件取得する設計（パターンC）でもあり、N+1解消後も1,534msかかっているのはこの10,000件分のPHP側処理（配列構築・JSON化）のコスト。件数が増え続ける限り根本的な解決にはならないため、フロントと調整の上でページネーション導入を別途検討する必要がある。

## よくある失敗

**❌ クエリを1本読んで「これが遅そう」と決め打ちして直す**
**✅ クエリログで実際のクエリ数を数え、EXPLAINで実行計画を見てから直す**

**❌ HTTP経由でcurlして測る（認証・ミドルウェアのオーバーヘッドが混ざる）**
**✅ tinkerでコントローラー/モデルのメソッドを直接呼んでDB部分だけを測る**

**❌ 修正後に「速くなった気がする」で終える**
**✅ 修正前と同じ方法で再測定し、数字で比較する**

**❌ テストが落ちたら全部自分の修正のせいだと思い込む**
**✅ `git stash` で切り分け、修正前から落ちていたか確認する**
