variable "rds_instance_id" {
  type    = string
  default = "house-hold-api-rds"
}

variable "stop_cron" {
  description = "RDS停止時刻 (Asia/Tokyo)"
  type        = string
  default     = "cron(30 23 ? * * *)"
}

variable "start_cron" {
  description = "RDS起動時刻 (Asia/Tokyo)"
  type        = string
  default     = "cron(30 7 ? * * *)"
}
