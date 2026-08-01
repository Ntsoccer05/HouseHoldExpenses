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

## 使い方（実行前に必ず読むこと）

**このディレクトリのTerraformコードはまだ一度も `terraform apply` していない。**
既存リソース（ECS/ALB/RDS/CloudFront等）は現状すべてコンソールから作成されたものであり、Terraform stateには
一切登録されていない。そのままこのコードで `terraform apply` すると、Terraformは「まだ存在しない」と誤認して
**重複したリソースを新規作成しようとする**（`serverless-migration-lessons-learned.md` 3-17と同種の事故）。

**実行する場合は、必ず以下の順序を踏むこと（ユーザー自身の判断・実行を推奨。破壊的操作を伴うため自動実行しない）。**

1. `bootstrap/` を最初に一度だけ apply し、tfstate用S3バケット・DynamoDBロックテーブル・GitHub OIDC用IAMロールを作る
2. `envs/prod/backend.tf` の bucket名を bootstrap の出力に合わせて更新し `terraform init`
3. **既存リソースを `terraform import` でstateに取り込む**（ECSクラスター、サービス、タスク定義、ALB、ターゲットグループ、セキュリティグループ、CloudFrontディストリビューション、RDSインスタンス 等）。import対象リソースのアドレス一覧は `envs/prod/main.tf` 内のコメントを参照
4. `terraform plan` で **差分が実質ゼロ（0 to add, 0 to destroy）** になることを確認してから初めて `apply`
5. 変更を加える場合も、必ず `plan` の内容を人間が確認してから `apply` する

## compute_backend の切替で何が起きるか

| リソース | `ecs` | `lambda` |
|---|---|---|
| ECSクラスター/サービス/タスク定義 | 稼働 | `desired_count=0`で維持（削除はしない。すぐ戻せるように） |
| ALB | 稼働 | 稼働はし続けるが CloudFront からは参照されなくなる |
| Lambda (fpm/console) | 定義のみ（`publish=false`で待機） | 稼働 |
| CloudFrontのAPI系オリジン | ALB | Lambda Function URL |
| EventBridge Scheduler | ECS+RDS 夜間停止 | RDSのみ夜間停止（Lambdaは呼ばれた分だけ課金なので停止不要） |

**ECS/ALBは`lambda`選択時も即座には削除しない。** 本当に不要と判断してから
`terraform apply -var="compute_backend=lambda" -var="destroy_idle_ecs=true"` のように明示的に削除する
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
