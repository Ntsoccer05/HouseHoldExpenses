# cdn モジュールについて（設計判断のメモ）

`E11UZMEXBMM184`（本番CloudFrontディストリビューション）は **あえてTerraformの
`aws_cloudfront_distribution`リソースとして丸ごと再定義していない**。理由:

- 現在10個の`ordered_cache_behavior`、2つのS3オリジン（それぞれ個別のOrigin Access Control）、
  カスタムキャッシュポリシー`FrontHouseHoldPolicy`など、属性数が非常に多い
- これを`terraform import`して`plan`が完全に一致するまで細部を詰める作業は、
  1文字でも属性が食い違うと**サイト全体（フロント・API・管理画面すべて）に影響する**、
  本リポジトリの中で最もブラスト半径が大きい変更になる
- 一方、今回の移行で実際に変える必要があるのは **ALB用オリジン1個だけ**
  （`house-hold-app-alb-1263701531.ap-northeast-1.elb.amazonaws.com`, HTTPのみ, ポート8080）
  であり、8つのビヘイビアはこのオリジンIDを参照しているだけでビヘイビア自体は無変更でよい
  （設計書2.2節の通り）

そのため、**オリジン1個のドメイン・プロトコルだけを安全に差し替えるスクリプト**
（`switch_origin.sh`）を用意した。`get-distribution-config`→ETag付き`update-distribution`という
AWSの標準的な安全パターンを使っており、対象オリジン以外は一切変更しない。

将来的にディストリビューション全体をTerraform管理下に置きたい場合は、`aws cloudfront
get-distribution-config`の出力をそのまま`aws_cloudfront_distribution`のHCLに機械的に変換し、
`terraform import`→`plan`で差分ゼロを確認してから、という手順を踏むこと。

## 2026-08-01 の本番障害対応で実施した変更（Terraform管理外のまま、AWS CLIで直接適用）

Lambda移行後のカットオーバーで発覚した問題を解消するため、`switch_origin.sh`が想定する
「オリジン1個のドメイン差し替えのみ」を超える変更を行った。以下は現在の実際のライブ設定であり、
`aws cloudfront get-distribution-config --id E11UZMEXBMM184`で常に最新値を確認すること。

1. **オリジンをLambda Function URLからAPI Gatewayへ変更**（`api_origin_domain`が指す先）。
   Function URL + OACでは原因不明のAccessDeniedExceptionが解消できず断念し、
   API Gateway HTTP API（AWS_PROXY統合）に切り替えた。

2. **カスタムキャッシュポリシー`FrontHouseHoldPolicy`が`Host`ヘッダーをオリジン転送
   ホワイトリストに含んでいたことが判明**。CloudFrontはCache PolicyとOrigin Request Policyの
   ヘッダーリストの**和集合**をオリジンに転送するため、Origin Request Policy側をどれだけ絞っても
   このHost転送は止まらず、API Gatewayのリージョナルエンドポイントが独自に行う
   Hostヘッダー検証で403を返し続けていた。
   - `/api/*` ビヘイビア: キャッシュポリシーをAWS管理の`Managed-CachingDisabled`に変更
     （API応答はそもそもキャッシュ不要なため）
   - 他6ビヘイビア（`/JMOJ...*`, `/css/filament/*`, `/js/filament/*`, `/vendor/*`,
     `/livewire/*`, `/storage/images/*`）: `FrontHouseHoldPolicy`からHostヘッダーだけを
     除いた複製ポリシー`FrontHouseHoldPolicyNoHost`を新規作成し、差し替えた

3. **静的アセット配信のアーキテクチャギャップに対応**。ECS+Nginxは`public/`配下の
   静的ファイル（css/js/vendor）をLaravelに到達する前に直接配信していたが、
   Lambda(Bref)にはそのWebサーバー層が存在せず、全リクエストがLaravelのルーターを通る。
   `/css/filament/*`等に対応するLaravelルートは存在しないため、SPA用キャッチオール
   ルートにフォールバックしていた。また`/vendor/livewire/livewire.js`はLivewire
   パッケージ自身の動的配信ルートを経由するが、Bref上では空レスポンスを返す不具合があった。
   → 新規S3バケット`house-hold-api-static-assets`（Terraform管理、`modules/cdn/main.tf`参照）
   を作成し、`public/css/filament`, `public/js/filament`, `public/vendor`を同期。
   CloudFrontのビヘイビアを以下のように変更・追加した:
   - `/css/filament/*`, `/js/filament/*`, `/vendor/*`: オリジンを`house-hold-api-static-assets`
     （S3、`Managed-CachingOptimized`ポリシー）に変更
   - `/livewire/livewire.js`, `/livewire/livewire.min.js.map`:
     一般の`/livewire/*`（Lambda、`/livewire/update`等のAJAXエンドポイント用）より
     **手前に**新規ビヘイビアとして挿入し、同じS3オリジンへ向ける
     （CloudFrontはビヘイビアリストの並び順で最初にマッチしたものを使うため順序が重要）

4. **Lambda環境変数にAPP_URL・FILAMENT_PATH等、ECSの`.env`から引き継がれていなかった
   値を追加**。`route:cache`はビルド時点の環境変数をルート定義に焼き込むため、
   Lambdaのランタイム環境変数を後から変更しても反映されない。正しい環境変数を揃えた上で
   `route:cache`からデプロイ成果物を再ビルド・再アップロードする必要があった。
   詳細は`modules/lambda_bref/variables.tf`の`environment`変数コメントを参照。

これらは現状すべてAWS CLIによる直接適用のみで、Terraformコードには未反映（CloudFront
ディストリビューション本体を管理外としている設計判断の帰結）。ビヘイビア・ポリシーの
完全なコード化は今後の課題。
