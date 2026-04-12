# OpenAPI仕様更新ガイド

## 1. 実装内容の確認

まず、実装したファイルを確認して、以下を把握します：

###確認項目

1. **新しいエンドポイント**
   - HTTPメソッド（GET, POST, PUT, DELETE）
   - パス（例: `/new-endpoint`, `/resource/{id}` など）
   - リクエスト/レスポンス形式

2. **新しいデータモデル**
   - リクエスト/レスポンスのスキーマ
   - プロパティ型（string, integer, array など）
   - 必須フィールド

3. **パラメータ**
   - クエリパラメータ（ページネーション、フィルタなど）
   - パスパラメータ（ID, ハッシュなど）

4. **セキュリティ要件**
   - 認証が必要か（BearerAuth）
   - ロール制限があるか

## 2. エンドポイント追加手順

### Step 1: 既存パターン確認

```bash
Grep('/IncomeCategory', 'api/openapi.yaml')
Grep('GET.*post:', 'api/paths/')
```

### Step 2: パスをopenapi.yamlに追加

```yaml
# api/openapi.yaml の paths セクションに追加

/new-resource:
  get:
    tags:
      - ResourceName          # 既存のタグを使用
    summary: リソース一覧取得
    description: ユーザーのリソース一覧を取得
    security:
      - BearerAuth: []
    parameters:
      - $ref: './parameters/common.yaml#/PageParameter'
      - $ref: './parameters/common.yaml#/PerPageParameter'
    responses:
      '200':
        $ref: './responses/common.yaml#/ResourceArraySuccess'
      '401':
        $ref: './responses/common.yaml#/Unauthorized'
```

### Step 3: スキーマが必要な場合は追加

```bash
# 1. 新しいスキーマファイルまたは既存ファイルに追加
# 例: api/schemas/domain.yaml

Resource:
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

# 2. openapi.yaml の components.schemas に参照を追加
Resource:
  $ref: './schemas/domain.yaml#/Resource'
```

## 3. パラメータ再利用パターン

### よくある再利用可能なパラメータ

| パラメータ | 用途 | 定義位置 |
|----------|------|--------|
| `page`, `per_page` | ページネーション | `parameters/common.yaml` |
| `category_id`, `type_id` | フィルタリング | `parameters/common.yaml` |
| `year`, `month` | 期間指定 | `parameters/common.yaml` |
| `{id}`, `{hash}` | パスパラメータ | `parameters/common.yaml` |
| `{provider}` | OAuth プロバイダー | `parameters/common.yaml` |

### 新しい共通パラメータの追加

共通で使うパラメータなら `api/parameters/common.yaml` に追加：

```yaml
# api/parameters/common.yaml

SearchParameter:
  name: search
  in: query
  schema:
    type: string
  description: キーワード検索

SortParameter:
  name: sort_by
  in: query
  schema:
    type: string
    enum: [created_at, updated_at, name]
  description: ソート対象フィールド
```

## 4. 応答定義再利用パターン

### よくある再利用可能な応答

| 応答 | 用途 | 定義位置 |
|------|------|--------|
| `Success` | 200 OK + MessageResponse | `responses/common.yaml` |
| `Unauthorized` | 401 認証失敗 | `responses/common.yaml` |
| `ValidationError` | 422 バリデーション失敗 | `responses/common.yaml` |
| `[Entity]Success` | 201 + エンティティ | `responses/common.yaml` |
| `[Entity]ArraySuccess` | 200 + 配列 | `responses/common.yaml` |

### 新しい共通応答の追加

頻繁に使う応答パターンなら `api/responses/common.yaml` に追加：

```yaml
# api/responses/common.yaml

ReportSuccess:
  description: レポート取得成功
  content:
    application/json:
      schema:
        $ref: '../schemas/transaction.yaml#/Report'

ReportArraySuccess:
  description: レポート一覧取得成功
  content:
    application/json:
      schema:
        type: array
        items:
          $ref: '../schemas/transaction.yaml#/Report'
```

## 5. ファイル更新チェックリスト

エンドポイント追加時、以下を確認：

### openapi.yaml の paths セクション
- [ ] 新しいパス追加
- [ ] HTTPメソッド正しい
- [ ] tags に既存タグを使用
- [ ] security 定義（必要なら）
- [ ] parameters は $ref で再利用
- [ ] responses は $ref で再利用

### openapi.yaml の components.schemas
- [ ] 新しいスキーマへの参照を追加
- [ ] 相対パス `./schemas/` 正しい

### api/schemas/[domain].yaml
- [ ] スキーマ定義正しい
- [ ] 全プロパティに type 指定
- [ ] 必須フィールドは required に記載
- [ ] example で具体的な値を指定

### api/parameters/common.yaml
- [ ] 共通パラメータ新規追加の場合

### api/responses/common.yaml
- [ ] 共通応答新規追加の場合

## 6. バージョン管理

OpenAPI仕様のバージョンを更新：

```yaml
# api/openapi.yaml の info セクション

info:
  title: HouseHold Expenses API
  version: 1.1.0  # 1.0.0 → 1.1.0 (新機能追加)
```

**ルール**:
- 機能追加 → マイナーバージョンアップ (1.0.0 → 1.1.0)
- バグ修正のみ → パッチバージョンアップ (1.0.0 → 1.0.1)
- 破壊的変更 → メジャーバージョンアップ (1.0.0 → 2.0.0)

## 7. 検証

更新完了後、以下を確認：

### YAML構文確認
```bash
# Node.js でYAML解析テスト
node -e "require('js-yaml').load(require('fs').readFileSync('api/openapi.yaml'))"
```

### Swagger UIで表示確認
- https://editor.swagger.io を開く
- `File` → `Import URL` → ファイルパスを指定
- エラーが表示されないか確認

### 参照パス確認
- すべての `$ref` が正しい相対パスか
- ファイルが実際に存在するか
- スキーマ名が正しいか
