# API 仕様・定義

このディレクトリは HouseHold Expenses API の仕様書と定義を管理します。

## 📄 ファイル構成

```
api/
├── openapi.yaml          # OpenAPI 3.0 統合仕様書（全パス定義を含む）
├── schemas/              # スキーマ定義（再利用コンポーネント）
│   ├── user.yaml         # User, LoginRequest, RegisterRequest など
│   ├── category.yaml     # IncomeCategory, ExpenseCategory, 関連リクエスト
│   ├── transaction.yaml  # Transaction, Type, MonthlySummary など
│   └── common.yaml       # Error, MessageResponse など共通スキーマ
├── paths/                # エンドポイント定義（参考・ドキュメント用）
│   ├── health.yaml       # ヘルスチェック
│   ├── auth.yaml         # 認証関連エンドポイント
│   ├── categories.yaml   # カテゴリ管理エンドポイント
│   ├── transactions.yaml # 取引管理エンドポイント
│   └── social.yaml       # ソーシャルログイン
├── responses/            # 再利用可能な応答定義
│   └── common.yaml       # HTTP応答テンプレート (401, 422, 200など)
├── parameters/           # 再利用可能なパラメータ定義
│   └── common.yaml       # ページネーション、フィルタ、パスパラメータ
├── examples/             # リクエスト/レスポンス例（将来使用）
└── README.md             # このファイル
```

## 📋 モジュール構成の説明

### ファイル参照の関係

- **openapi.yaml**: メインの API 仕様ファイル。すべてのパスとスキーマを含む
  - `paths/` 配下のファイルは、openapi.yaml に統合済みの定義を参考目的で保持
  - Swagger UI などのツールでは `openapi.yaml` を指定

- **schemas/**: 再利用可能なスキーマ定義
  - openapi.yaml の `components.schemas` から `$ref` で参照
  - 各ファイルは関連スキーマをグループ化

- **paths/**: エンドポイント定義（参考用）
  - openapi.yaml に統合されているため、これらは参考・ドキュメント用
  - 機能ごとにグループ分けされており、どのエンドポイントがどのファイルに関連するかを示す

- **responses/**: 再利用可能な HTTP 応答定義
  - openapi.yaml の `paths[*].responses` から `$ref` で参照
  - 共通の応答パターンをテンプレート化（401 Unauthorized、200 Success など）
  - 重複を削減し、応答仕様の一元管理を実現

- **parameters/**: 再利用可能なパラメータ定義
  - openapi.yaml の `paths[*].parameters` から `$ref` で参照
  - ページネーション（page, per_page）、フィルタ、パスパラメータを共通化
  - パラメータ仕様の変更が一箇所で完結

## 📝 新しいエンドポイント追加フロー

新しいエンドポイントやスキーマを追加する場合：

### 1. スキーマの定義
`schemas/` 配下の適切なファイルにリクエスト/レスポンススキーマを追加
- ユーザー関連: `schemas/user.yaml`
- カテゴリ関連: `schemas/category.yaml`
- 取引関連: `schemas/transaction.yaml`
- 共通スキーマ: `schemas/common.yaml`

### 2. エンドポイント定義
`openapi.yaml` の `paths` セクションに新しいパスを追加。応答とパラメータは再利用可能な定義を活用：
```yaml
/new-endpoint:
  post:
    tags:
      - TagName
    summary: 概要
    description: 説明
    parameters:
      - $ref: './parameters/common.yaml#/PageParameter'  # 再利用可能なパラメータ
    requestBody:
      required: true
      content:
        application/json:
          schema:
            $ref: './schemas/appropriate-file.yaml#/SchemaName'
    responses:
      '200':
        $ref: './responses/common.yaml#/Success'  # 再利用可能な応答
      '401':
        $ref: './responses/common.yaml#/Unauthorized'
```

### 3. 新しい応答パターンの場合
`responses/common.yaml` に新しい応答定義を追加：
```yaml
CustomResponse:
  description: カスタム応答
  content:
    application/json:
      schema:
        $ref: '../schemas/appropriate-file.yaml#/SchemaName'
```

### 4. 新しいパラメータパターンの場合
`parameters/common.yaml` に新しいパラメータ定義を追加：
```yaml
CustomParameter:
  name: custom_param
  in: query
  schema:
    type: string
  description: カスタムパラメータ
```

### 5. 参考ドキュメント
`paths/` 配下の適切なファイルにも記述（ドキュメント目的）

### 6. コンポーネント追加
`openapi.yaml` の `components.schemas` に新スキーマへの参照を追加

## 🚀 使い方

### Swagger UI で表示

**オンライン Swagger Editor**:
1. https://editor.swagger.io にアクセス
2. `File` → `Import URL`
3. `api/openapi.yaml` の URL を入力
4. または、ファイルをドラッグ&ドロップ

**ローカル Swagger UI**:
```bash
# Docker で Swagger UI を起動
docker run -p 8081:8080 \
  -v $(pwd)/api:/usr/share/nginx/html/api \
  -e BASE_URL=/api/openapi.yaml \
  swaggerapi/swagger-ui

# http://localhost:8081 にアクセス
```

### API クライアント自動生成

```bash
# OpenAPI Generator で SDK を生成
npx @openapitools/openapi-generator-cli generate \
  -i api/openapi.yaml \
  -g typescript-axios \
  -o src/generated/api-client

# または JavaScript
openapi-generator-cli generate \
  -i api/openapi.yaml \
  -g javascript \
  -o src/generated/api-client
```

## 📝 API 仕様書の更新

新しい API エンドポイントを追加する際のフロー：

### 1. `api/openapi.yaml` を更新

```yaml
paths:
  /new-endpoint:
    post:
      tags:
        - NewFeature
      summary: 新しい機能
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/NewRequest'
      responses:
        '200':
          description: 成功
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/NewResponse'

components:
  schemas:
    NewRequest:
      type: object
      properties:
        field1:
          type: string
    
    NewResponse:
      type: object
      properties:
        id:
          type: integer
```

### 2. バックエンド実装（Laravel）

```bash
php artisan make:controller NewFeatureController
php artisan make:request NewFeatureRequest
```

### 3. フロントエンド実装

```typescript
// 自動生成されたクライアントを使用
import { NewFeatureApi } from './generated/api-client';

const api = new NewFeatureApi();
api.postNewEndpoint({ field1: 'value' });
```

## ✅ 品質管理

### OpenAPI ファイルの検証

```bash
# OpenAPI Validator
npm install -g @ibm-apiconnect/openapi-validator

openapi-validator api/openapi.yaml
```

### リント

```bash
# spectral でスタイルチェック
npm install -g @stoplight/spectral-cli

spectral lint api/openapi.yaml
```

## 📖 参考資料

- [OpenAPI 3.0 仕様](https://spec.openapis.org/oas/v3.0.3)
- [Swagger UI ドキュメント](https://swagger.io/tools/swagger-ui/)
- [OpenAPI Generator](https://openapi-generator.tech/)

## 🔄 バージョン管理

`api/openapi.yaml` の `info.version` でバージョンを管理します：

```yaml
info:
  title: HouseHold Expenses API
  version: 1.0.0        # ← セマンティックバージョニング
```

**バージョンアップのタイミング**:
- `パッチ` (1.0.1): バグ修正
- `マイナー` (1.1.0): 新しい機能（後方互換性あり）
- `メジャー` (2.0.0): 破壊的変更（後方互換性なし）

## 🔐 セキュリティ

API キーやシークレットをコミットしないこと：

```yaml
❌ # 絶対に含めない
securitySchemes:
  ApiKey:
    type: apiKey
    name: X-API-Key
    in: header
    default: "sk-1234567890"

✅ # このようにする
# docs/README に運用手順を記載
```

## 📞 サポート

API 仕様に関する質問は：
- GitHub Issues で質問
- Pull Request でスキーマ提案
