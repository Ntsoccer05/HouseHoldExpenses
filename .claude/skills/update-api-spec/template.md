# OpenAPI仕様更新テンプレート

## エンドポイント追加テンプレート

```yaml
/new-endpoint:
  post:
    tags:
      - FeatureName
    summary: 機能の概要
    description: 詳細説明
    security:
      - BearerAuth: []
    parameters:
      - $ref: './parameters/common.yaml#/PageParameter'
    requestBody:
      required: true
      content:
        application/json:
          schema:
            $ref: './schemas/domain.yaml#/RequestSchema'
    responses:
      '200':
        $ref: './responses/common.yaml#/SuccessResponse'
      '401':
        $ref: './responses/common.yaml#/Unauthorized'
      '422':
        $ref: './responses/common.yaml#/ValidationError'
```

## スキーマ定義テンプレート

```yaml
# api/schemas/domain.yaml に追加

NewSchema:
  type: object
  properties:
    id:
      type: integer
      example: 1
    name:
      type: string
      example: 例
    created_at:
      type: string
      format: date-time
      example: 2026-04-12T10:30:00Z

NewRequestSchema:
  type: object
  required:
    - field1
  properties:
    field1:
      type: string
      example: 値
    field2:
      type: integer
      nullable: true
```

## パラメータ定義テンプレート

```yaml
# api/parameters/common.yaml に追加

CustomParameter:
  name: custom_param
  in: query
  schema:
    type: string
  description: カスタムパラメータの説明

FilterParameter:
  name: filter_type
  in: query
  schema:
    type: string
    enum: [type1, type2, type3]
  description: フィルタタイプ
```

## 応答定義テンプレート

```yaml
# api/responses/common.yaml に追加

CustomSuccess:
  description: カスタム成功応答
  content:
    application/json:
      schema:
        $ref: '../schemas/domain.yaml#/CustomSchema'

CustomArraySuccess:
  description: カスタム配列応答
  content:
    application/json:
      schema:
        type: array
        items:
          $ref: '../schemas/domain.yaml#/CustomSchema'
```

## components.schemas への参照追加テンプレート

```yaml
# api/openapi.yaml の components.schemas に追加

NewSchema:
  $ref: './schemas/domain.yaml#/NewSchema'

NewRequestSchema:
  $ref: './schemas/domain.yaml#/NewRequestSchema'
```
