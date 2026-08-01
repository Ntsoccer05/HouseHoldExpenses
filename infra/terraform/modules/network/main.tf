##############################################################################
# network
#
# 既存VPC(vpc-0c3c823b2089c4354, 10.0.0.0/20)は変更せず再利用する。
# コンソール実査の結果、以下が判明した:
#   - パブリックサブネット2つ(10.0.1.0/24 / 10.0.3.0/24): 現在ECS/ALBが使用中
#   - プライベートサブネット2つ(10.0.0.0/24 / 10.0.2.0/24): 作成済みだが未使用
#     (private1-aには専用ルートテーブルrtb-005e62414043ad5a5が既にあるが、
#      ローカルルートのみでNAT/IGWへの0.0.0.0/0ルートは無い。
#      private1-cはVPCのメインルートテーブルに暗黙的に乗っているだけ)
#
# → Lambda用に新規サブネットを作る必要はなく、この2つの既存プライベートサブネットを
#   そのまま使う。private1-cを明示的に専用ルートテーブルへ付け替えた上で、
#   NAT経由の0.0.0.0/0ルートを追加する。
##############################################################################

data "aws_vpc" "this" {
  id = var.vpc_id
}

data "aws_subnet" "public" {
  for_each = toset(var.public_subnet_ids)
  id       = each.value
}

data "aws_subnet" "private" {
  for_each = toset(var.private_subnet_ids)
  id       = each.value
}

# private1-a に既存のルートテーブル(rtb-005e62414043ad5a5)を "private" 共通ルートテーブルとして採用し、
# import後はTerraformで管理する。private1-cもここに寄せる。
resource "aws_route_table" "private" {
  vpc_id = var.vpc_id

  tags = {
    Name = "house-hold-app-rtb-private-shared"
  }

  # NOTE: 初回は `terraform import aws_route_table.private rtb-005e62414043ad5a5` で
  # 既存のprivate1-a用ルートテーブルを取り込むこと（新規作成すると重複する）。
}

resource "aws_route_table_association" "private" {
  for_each       = data.aws_subnet.private
  subnet_id      = each.value.id
  route_table_id = aws_route_table.private.id
}

# --- NAT: instance / gateway / none ----------------------------------------

resource "aws_eip" "nat" {
  count  = var.nat_type != "none" ? 1 : 0
  domain = "vpc"

  tags = {
    Name = "house-hold-app-nat-eip"
  }
}

# NAT Gateway（AWS管理・高価。COST_ESTIMATE.md参照）
resource "aws_nat_gateway" "this" {
  count         = var.nat_type == "gateway" ? 1 : 0
  allocation_id = aws_eip.nat[0].id
  subnet_id     = var.public_subnet_ids[0]

  tags = {
    Name = "house-hold-app-nat-gw"
  }
}

# NATインスタンス（自前・安価。既知の落とし穴を踏まえた実装）
data "aws_ami" "nat_instance" {
  count       = var.nat_type == "instance" ? 1 : 0
  most_recent = true
  owners      = ["amazon"]

  filter {
    name   = "name"
    values = ["al2023-ami-*-arm64"]
  }
  filter {
    name   = "architecture"
    values = ["arm64"]
  }

  # NOTE(lessons-learned 3-3): most_recent=true はAMIドリフトの原因になるため、
  # 初回apply後は下の lifecycle.ignore_changes で ami をピン留めする。
}

resource "aws_security_group" "nat_instance" {
  count       = var.nat_type == "instance" ? 1 : 0
  name        = "house-hold-app-nat-instance-sg"
  description = "NAT instance for Lambda outbound (private subnets only)"
  vpc_id      = var.vpc_id

  # lessons-learned 3-10: CIDRは「だいたい範囲」ではなく実サブネットCIDRと機械的に突き合わせる
  dynamic "ingress" {
    for_each = data.aws_subnet.private
    content {
      description = "from private subnet ${ingress.value.id}"
      from_port   = 0
      to_port     = 0
      protocol    = "-1"
      cidr_blocks = [ingress.value.cidr_block]
    }
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "house-hold-app-nat-instance-sg"
  }
}

resource "aws_iam_role" "nat_instance" {
  count = var.nat_type == "instance" ? 1 : 0
  name  = "house-hold-app-nat-instance-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect    = "Allow"
      Principal = { Service = "ec2.amazonaws.com" }
      Action    = "sts:AssumeRole"
    }]
  })
}

# lessons-learned 4章: 「シェルアクセスが必要になり得るリソースには構築時点でSSMを付与しておく」
resource "aws_iam_role_policy_attachment" "nat_instance_ssm" {
  count      = var.nat_type == "instance" ? 1 : 0
  role       = aws_iam_role.nat_instance[0].name
  policy_arn = "arn:aws:iam::aws:policy/AmazonSSMManagedInstanceCore"
}

resource "aws_iam_instance_profile" "nat_instance" {
  count = var.nat_type == "instance" ? 1 : 0
  name  = "house-hold-app-nat-instance-profile"
  role  = aws_iam_role.nat_instance[0].name
}

resource "aws_instance" "nat" {
  count                       = var.nat_type == "instance" ? 1 : 0
  ami                         = data.aws_ami.nat_instance[0].id
  instance_type               = var.nat_instance_type
  subnet_id                   = var.public_subnet_ids[0]
  vpc_security_group_ids      = [aws_security_group.nat_instance[0].id]
  iam_instance_profile        = aws_iam_instance_profile.nat_instance[0].name
  associate_public_ip_address = true
  source_dest_check           = false # NAT動作に必須（送信元/宛先チェック無効化）

  # lessons-learned 3-10 の再発防止:
  #   - インターフェース名をハードコードせず `ip route show default` で動的検出する
  #   - Docker等コンテナランタイムは入れない(FORWARDチェーンのDROPポリシー化を避ける)
  user_data = <<-EOF
    #!/bin/bash
    set -eux
    sysctl -w net.ipv4.ip_forward=1
    echo "net.ipv4.ip_forward = 1" >> /etc/sysctl.conf

    IFACE=$(ip route show default | awk '{print $5; exit}')
    iptables -t nat -A POSTROUTING -o "$IFACE" -j MASQUERADE
    iptables -P FORWARD ACCEPT

    # 再起動後も設定を復元する
    mkdir -p /etc/nat-instance
    cat > /etc/systemd/system/nat-instance.service <<'UNIT'
    [Unit]
    Description=NAT instance MASQUERADE setup
    After=network-online.target
    Wants=network-online.target

    [Service]
    Type=oneshot
    RemainAfterExit=yes
    ExecStart=/bin/bash -c 'IFACE=$$(ip route show default | awk "{print \$5; exit}"); iptables -t nat -C POSTROUTING -o $$IFACE -j MASQUERADE 2>/dev/null || iptables -t nat -A POSTROUTING -o $$IFACE -j MASQUERADE; iptables -P FORWARD ACCEPT'

    [Install]
    WantedBy=multi-user.target
    UNIT
    systemctl enable nat-instance.service
  EOF

  tags = {
    Name = "house-hold-app-nat-instance"
  }

  lifecycle {
    ignore_changes = [ami] # lessons-learned 3-3: AMI更新は意図的なタイミングでのみ行う
  }
}

resource "aws_eip_association" "nat" {
  count         = var.nat_type == "instance" ? 1 : 0
  instance_id   = aws_instance.nat[0].id
  allocation_id = aws_eip.nat[0].id
}

# --- private route table への 0.0.0.0/0 ルート -------------------------------

resource "aws_route" "private_default" {
  count                  = var.nat_type != "none" ? 1 : 0
  route_table_id         = aws_route_table.private.id
  destination_cidr_block = "0.0.0.0/0"
  nat_gateway_id         = var.nat_type == "gateway" ? aws_nat_gateway.this[0].id : null
  network_interface_id   = var.nat_type == "instance" ? aws_instance.nat[0].primary_network_interface_id : null
}
