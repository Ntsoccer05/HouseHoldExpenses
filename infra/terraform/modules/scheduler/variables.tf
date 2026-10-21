variable "rds_instance_id" {
  type    = string
  default = "house-hold-api-rds"
}

variable "schedules" {
  description = <<-EOT
    RDS停止・起動のスケジュール (Asia/Tokyo)。
    days は weekday(平日) / holiday(土日祝) / all。cronで発火した後、Lambdaが当日の種別と一致する時だけ実行する。
  EOT
  type = map(object({
    cron   = string
    action = string
    days   = string
  }))
  default = {
    "stop-night" = {
      cron   = "cron(0 1 ? * * *)"
      action = "stop"
      days   = "all"
    }
    "start-weekday-morning" = {
      cron   = "cron(0 6 ? * MON-FRI *)"
      action = "start"
      days   = "weekday"
    }
    "stop-weekday-midday" = {
      cron   = "cron(0 11 ? * MON-FRI *)"
      action = "stop"
      days   = "weekday"
    }
    "start-weekday-evening" = {
      cron   = "cron(0 17 ? * MON-FRI *)"
      action = "start"
      days   = "weekday"
    }
    "start-holiday-morning" = {
      cron   = "cron(0 7 ? * * *)"
      action = "start"
      days   = "holiday"
    }
  }
}
