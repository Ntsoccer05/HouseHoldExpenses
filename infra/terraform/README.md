# infra/terraform — ECS ⇔ サーバーレス(Bref Lambda) 切替可能なIaC

## 設計方針（重要）

当初の依頼は「サーバーレスのIaC」と「ECS運用をロールバック用に残すIaC」を別々に、という内容だったが、
**CloudFront（`E11UZMEXBMM184`）・RDS・S3は両構成で共有する単一のリソース**であり、これをTerraformの別スタック（別state）で
二重管理すると、片方をapplyするともう片方のstateと実態が食い違う／削除しようとする、という事故が起きる
（同じ実リソースを2つのstateが取り合う状態）。

そのため本リポジトリでは **1つのTerraform stack（`envs/prod`）の中で `var.compute_backend` を `"ecs"` / `"lambda"` に
切り替える設計**にした。ECS一式（クラスター、サービス、タスク定義、ALB、ターゲットグループ）は
`modules/ecs_service` として**常にコードとして存在し続ける**ため、「ECS運用内容もIaCとして残す」という要件は満たしつつ、
切り戻しは

```
terraform apply -var="compute_backend=ecs"
```

の一発（CloudFrontのオリジンとRoute53向き先も自動でALBに戻る）で完結する。逆にサーバーレス化は

```
terraform apply -var="compute_backend=lambda"
```

これにより「別々のIaCなのに整合しない」という事故そのものを設計上排除している。

## ディレクトリ構成

```
infra/terraform/
├── bootstrap/            # 初回のみ・ローカルstate運用。tfstate用S3+DynamoDB、GitHub OIDC、CI用IAMロールを作成
├── modules/
│   ├── network/           # 既存VPC/サブネットのdata source + NAT(instance|gateway|none)切替
│   ├── ecs_service/        # ECSクラスター/サービス/タスク定義/ALB/ターゲットグループ（現状構成そのまま）
│   ├── lambda_bref/         # Bref Lambda (fpm-runtime / console-runtime) + Function URL
│   ├── scheduler/           # EventBridge Scheduler（ECS+RDS版 / RDSのみ版）
│   └── cdn/                # 既存CloudFrontディストリビューションの管理（オリジンをcompute_backendで切替）
└── envs/
    └── prod/               # 本番環境。上記モジュールを束ねるルート
```

## 現在の状態（2026-08-01時点）

- `bootstrap/`: apply済み。tfstate用S3(`household-expenses-terraform-state`)・DynamoDB(`household-expenses-terraform-lock`)・
  GitHub OIDC(既存の共有プロバイダをimportして再利用)・IAMロール(`household-github-actions-deploy`, AdministratorAccess)作成済み
- `envs/prod`: `compute_backend=lambda`で本番稼働中。**ECS/ALB一式は完全削除済み**
  （`destroy_idle_ecs=true`。クラスター/サービス/タスク定義/ALB/リスナー3本/ターゲットグループ/ECRリポジトリ/
  CloudWatchロググループをterraform destroy。ロールバック用の待機は終了し、`compute_backend=ecs`へ戻すには
  `destroy_idle_ecs=false`にしてapplyし直す＝ゼロから再作成が必要）
- **RDSは24時間稼働（`enable_rds_night_stop=false`）**。Terraform管理のRDS夜間停止スケジュールを削除。
  加えて、Lambda移行後も気づかれず毎朝ECSを再起動し続けていたlegacy CloudFormationスタック
  `household-scheduler`（ECS/RDS夜間停止用の旧仕組み、`infra/scheduler/`）も削除し、二重管理を解消した。
  24/7化による追加コストは`COST_ESTIMATE.md`5章参照
- **CloudFrontのオリジンは切替済み**。ただしLambda Function URLではなく**API Gateway(HTTP API)**経由
  （Function URLはAccessDeniedExceptionが解消できず断念。詳細は`modules/cdn/README.md`参照）
- 静的アセット（Filament/Livewireのcss/js/vendor）配信用に新規S3バケット`house-hold-api-static-assets`を
  追加し、該当CloudFrontビヘイビアをそちらに向けている（`modules/cdn/main.tf`、Terraform管理下）
- **本番でAPI・SPA・Filament管理画面（ログイン〜ダッシュボード）すべて実機動作確認済み**
- CloudFrontディストリビューション本体・カスタムキャッシュポリシー等の細部は引き続きTerraform管理外
  （`modules/cdn/README.md`の「2026-08-01の本番障害対応で実施した変更」の節に現在のライブ設定を記載）
- `.github/workflows/cicd.yml`（ECSデプロイ用）はpushトリガーを外し`workflow_dispatch`のみに無効化済み。
  今後のバックエンドデプロイは`.github/workflows/deploy-backend-serverless.yml`（Lambda）を使う

## 使い方（今後このコードを再適用する場合）

`terraform plan` で差分の内容を必ず人間が確認してから `apply` すること。特に`compute_backend`を切り替える
apply、CloudFrontのオリジン切替は本番トラフィックに影響するため、事前に内容を把握してから実行する。

## 【重要】Bref 3.x の RUNTIME_CLASS が機能しない問題（実機で発見・解決済み）

`bref/bref` の `php-82`(v23) レイヤーは `/opt/bootstrap` シェルスクリプトが
`export RUNTIME_CLASS="Bref\FunctionRuntime\Main"` を**無条件に**実行しており、
Lambda関数側で `RUNTIME_CLASS` 環境変数に別の値（`Bref\FpmRuntime\Main` や `Bref\ConsoleRuntime\Main`）を
設定しても**常に上書きされて無視される**。これは`serverless-migration-lessons-learned.md`(Bref 2.x時代)に
書いた「RUNTIME_CLASS未設定時のデフォルトフォールバック」問題とは別の、Bref 3.x特有の新しい罠。

- `Bref\FpmRuntime\Main` というクラスはBref 3.xにはそもそも存在しない（`FpmRuntime`名前空間にあるのは`FpmHandler`のみ）
- handlerに`public/index.php`や`artisan`のような**ファイルパス**を指定すると、`FunctionRuntime\Main`が
  ファイルを直接`require`してしまい、`public/index.php`は`$_SERVER`未整備によるTypeErrorでクラッシュ、
  `artisan`はファイル末尾の`exit()`でランタイムプロセスごと終了し**Lambdaがタイムアウトするまでハングする**
  （実機で両方とも再現・確認済み）

**解決策**: handlerを**クラス名**にする(`FunctionRuntime\Main`はBref標準の`Handler`インターフェースを
実装したクラスを正しく解決できる)。
- HTTP: `Bref\LaravelBridge\Http\HttpHandler`（bref/laravel-bridge同梱、追加実装不要）
- artisan: `App\Lambda\ArtisanHandler`（本リポジトリで新規作成。`Kernel::call()`でin-process実行し、
  サブプロセスやexit()に依存しない。`src/tests/Unit/Lambda/ArtisanHandlerTest.php`参照）

`modules/lambda_bref/main.tf`の`aws_lambda_function.app`/`console`のhandler設定とコメントを参照。

## compute_backend の切替で何が起きるか

| リソース | `ecs` | `lambda`（2026-08-01時点の実際の設定） |
|---|---|---|
| ECSクラスター/サービス/タスク定義/ALB/ECR | 稼働 | **削除済み**（`destroy_idle_ecs=true`。ロールバックには再作成が必要） |
| Lambda (fpm/console) | 定義のみ（`publish=false`で待機） | 稼働 |
| CloudFrontのAPI系オリジン | ALB | Lambda Function URL |
| EventBridge Scheduler (RDS夜間停止) | — | 停止（`enable_rds_night_stop=false`。RDSは24/7稼働） |

**当初は「`lambda`選択時もECS/ALBは即座に削除せず`desired_count=0`で待機」という設計だったが、
2026-08-01にLambda運用が安定稼働したと判断し`destroy_idle_ecs=true`で完全削除した。**
`ecs`へ切り戻す場合は`destroy_idle_ecs=false`にしてapplyし、ECS/ALB/ECRをゼロから再作成する必要がある
（`serverless-migration-lessons-learned.md` 3-13「削除し忘れの付随リソース」を踏まえ、EIP・SG等の孤立リソース確認も
このステップで行う）。

## NATについて

`modules/network`の`nat_type`変数で `"instance"`（安価・自前運用） / `"gateway"`（AWS管理・高価） / `"none"`
（VPCアタッチしない＝RDSに到達できないので実質使えない）を選べる。コストの違いは `COST_ESTIMATE.md` を参照。
デフォルトは `"instance"`。

## GitHub Actions側の追加設定

新設した `.github/workflows/deploy-backend-serverless.yml` は既存の `secrets.PRODUCTION_ENV`
（`AWS_REGION`, `AWS_ROLE_TO_ASSUME` 等がKEY=VALUE形式で入っている）に、以下のキーを追加する必要がある。

```
LAMBDA_ARTIFACT_BUCKET=house-hold-api-lambda-artifacts
LAMBDA_APP_FUNCTION_NAME=house-hold-app-api
LAMBDA_CONSOLE_FUNCTION_NAME=house-hold-app-console
```

`secrets.LARAVEL_PRODUCTION_ENV`（Laravelの`.env`相当）は既存のものをそのまま使うが、
`SESSION_DRIVER=file` / `CACHE_DRIVER=file` を `database` に、`email:strict,dns,spoof` を
使っている場合は `spoof` を除去するなど、Lambda移行前提のアプリ側修正を先に適用しておくこと
（設計書4章リスク1,2、`serverless-migration-lessons-learned.md`参照）。

このworkflowは`workflow_dispatch`のみで、pushトリガーはまだ付けていない
（既存の`cicd.yml`と同時に自動発火してECSとLambdaが競合しないようにするため）。
移行を確定したら`on.push`を有効化する。

## CloudFrontの切替（カットオーバー）

`modules/cdn/switch_origin.sh` を使う。対話式の確認プロンプトがあるため、
**人間が手元で実行する**（CIの自動実行には組み込んでいない。ブラスト半径が最大の操作のため）。

```bash
# Lambda移行後、動作確認が済んでから
OAC_ID=<lambda用OACのID> ./infra/terraform/modules/cdn/switch_origin.sh lambda <function-url-domain>

# 問題があれば即座にALBへ戻す
./infra/terraform/modules/cdn/switch_origin.sh ecs
```

## 適用前チェックリスト（`serverless-migration-lessons-learned.md` 4章より抜粋・本構成に関係する項目のみ）

- [ ] `,spoof`バリデーション除去、SESSION/CACHE DRIVERの`database`化など、アプリ側の事前修正（設計書4章リスク1,2）を**Lambda移行前に**実施済みか
- [ ] `route:cache`導入前に、`web.php`の`/health`・`/{any}`、`api.php`の`/health`・`/user`のクロージャルートをコントローラーに置き換えたか（既存コードに4箇所現存を確認済み）
- [ ] Lambda環境変数（DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD/APP_KEY等）の完全なリストをCI Secretsに用意したか
- [ ] NATインスタンスを使う場合、iptablesスクリプトでインターフェース名をハードコードしていないか、AMIにDockerが同梱されていないか
- [ ] `config:cache`はCIパイプラインに含めない（Filamentの`resources.path`がビルド環境の絶対パスで固定される問題を回避）
- [x] Lambda handlerはファイルパスではなくクラス名にする（上記「Bref 3.x の RUNTIME_CLASS が機能しない問題」参照）— 解決済み
