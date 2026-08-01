variable "vpc_id" {
  type = string
}

variable "private_subnet_ids" {
  type = list(string)
}

variable "rds_security_group_id" {
  description = "既存 house-hold-api-sg。RDSがこのSGからの3306を許可済み(0.0.0.0/0)のため、LambdaのSGを追加で許可し直す必要はないが、将来的にはこのLambda専用SGに絞り込むことを推奨する"
  type        = string
  default     = "sg-00da69b8788f033d3"
}

variable "artifact_bucket_name" {
  type    = string
  default = "house-hold-api-lambda-artifacts"
}

variable "deploy_object_key" {
  description = <<-EOT
    デプロイ用zipのS3キー。CIが毎回このキーに最新のzipを上書きし続ける固定キーにすること。
    (lessons-learned 3-18: ignore_changesで追跡除外している属性は、
     他の属性変更のついでに古いプレースホルダー値が再送されるリスクがあるため、
     デフォルト値自体を「放置されても安全な値」にしておく)
  EOT
  type        = string
  default     = "backend/latest.zip"
}

variable "bref_layer_arn" {
  description = <<-EOT
    Bref PHPレイヤーのARN。Bref 3.x以降はfpm-runtime/console-runtimeとも同一の"php-82"レイヤーを使う
    (2.x時代の"php-82-fpm"+"console"の2レイヤー構成から統合された)。
    バージョンは `vendor/bref/bref/layers.json` を参照して固定すること。
    最初はECSと同じx86_64で揃え、安定後にarm64へ切り替える(その際は"arm-php-82"に変更)。
  EOT
  type        = string
  default     = "arn:aws:lambda:ap-northeast-1:534081306603:layer:php-82:23"
}

variable "lambda_memory_mb" {
  type    = number
  default = 512
}

variable "lambda_timeout_seconds" {
  type    = number
  default = 28 # CloudFrontのオリジンタイムアウト(30秒)未満に収める
}

variable "environment" {
  description = <<-EOT
    Lambda環境変数。DB_PASSWORD/APP_KEY/MAIL_PASSWORD/GOOGLE_CLIENT_SECRET等の秘匿値、
    および FILAMENT_PATH（Filament管理画面の難読化パス。推測されにくいURLであること自体に
    セキュリティ上の意味があるため、Publicリポジトリに平文で書かない）は
    ここに書かず、GitHub Secrets経由でCIが `aws lambda update-function-configuration` で
    都度設定する(lessons-learned 3-1)。Terraform側は lifecycle.ignore_changes で
    environment を追跡除外するため、この default はあくまで「初回applyで最低限動く値」の
    ドキュメントとしての意味しか持たない(実運用の値はCIが都度上書きする)。

    【2026-08-01判明】ECSの.envから引き継ぐべきだったが漏れていた値
    (APP_URL, FILAMENT_PATH, SANCTUM_STATEFUL_DOMAINS等)がここに含まれていなかったため、
    Filament管理画面が正しくルーティングされない障害が発生した。route:cacheはビルド時点の
    環境変数をルート定義に焼き込むため、デプロイパイプライン(CI)側でこれらの値を
    渡さないままビルドすると、Lambdaのランタイム環境変数を後から直接変更しても反映されない。
    FILAMENT_PATHの実際の値はGitHub Secrets(PRODUCTION_ENV)側で管理し、CloudFrontの
    対応するordered_cache_behaviorのPathPatternと必ず一致させること
    (config('filament.path')のデフォルトは"admin")。

    APP_NAME・MAIL_FROM_NAMEは意図的にここに含めない: Bref/Lambdaのランタイムで
    マルチバイト文字を含む環境変数がgetenv()レベルで文字化けする問題が実機で確認され、
    AdminPanelProvider::brandName()およびconfig/mail.phpのデフォルト値に直接
    ハードコードすることで回避した(原因はBrefの/opt/bootstrap内部処理と推測されるが未特定)。
  EOT
  type        = map(string)
  default = {
    APP_ENV          = "production"
    APP_DEBUG        = "false"
    APP_URL          = "https://kake-pon.com"
    CLIENT_URL       = "https://kake-pon.com"
    LOG_CHANNEL      = "stderr"
    SESSION_DRIVER   = "database"
    CACHE_DRIVER     = "database"
    QUEUE_CONNECTION = "sync"

    SESSION_DOMAIN           = ".kake-pon.com"
    SESSION_SECURE_COOKIE    = "true"
    SANCTUM_STATEFUL_DOMAINS = "kake-pon.com,.kake-pon.com"

    # FILAMENT_PATHはここに書かない(上記description参照)。GitHub Secrets経由でCIが設定する。

    # SendGrid経由のメール送信。MAIL_PASSWORD(APIキー)のみCI管理の秘匿値。
    MAIL_DRIVER       = "smtp"
    MAIL_HOST         = "smtp.sendgrid.net"
    MAIL_PORT         = "587"
    MAIL_ENCRYPTION   = "tls"
    MAIL_FROM_ADDRESS = "no-reply@kake-pon.com"

    # 【実機検証で判明】/var/task は読み取り専用のため、Blade コンパイル済みビューの
    # 書き込み先を明示的に書き込み可能な /tmp 配下に向ける。
    # bref/laravel-bridge の BrefServiceProvider は config('view.compiled') が
    # 「既存の(読み取り専用な)ディレクトリ」を指していると誤って自動リダイレクトをスキップするため
    # (is_dir()チェックが、storage/framework/viewsがzipに読み取り専用のまま含まれていると
    #  trueになってしまう)、env変数で明示指定して回避する。
    VIEW_COMPILED_PATH = "/tmp/storage/framework/views"
  }
}

variable "warmup_enabled" {
  description = "コールドスタート対策としてEventBridge Schedulerで定期pingするかどうか"
  type        = bool
  default     = true
}

variable "warmup_schedule" {
  description = <<-EOT
    lessons-learned 3-20を踏襲: 深夜早朝(RDS停止時間帯とほぼ重なる0:00-5:00)を除いた
    利用時間帯のみ5分間隔でping。muuv-e-partsの実運用パターンを参考にした値。
  EOT
  type        = string
  default     = "cron(0/5 5-23 * * ? *)"
}
