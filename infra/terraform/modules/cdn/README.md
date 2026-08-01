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
